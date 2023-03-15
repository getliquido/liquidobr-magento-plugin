<?php

namespace Liquido\PayIn\Controller\Adminhtml\Liquido;

use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;

use Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use Liquido\PayIn\Helper\LiquidoOrderData;
use Liquido\PayIn\Helper\LiquidoConfigData;

use LiquidoBrl\PayInPhpSdk\Util\Config;
use LiquidoBrl\PayInPhpSdk\Model\RefundRequest;
use LiquidoBrl\PayInPhpSdk\Service\RefundService;
use LiquidoBrl\PayInPhpSdk\Util\Country;
use LiquidoBrl\PayInPhpSdk\Util\Currency;
use LiquidoBrl\PayInPhpSdk\Util\PayInStatus;

class RefundOrder extends Action
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $order;

    private LoggerInterface $logger;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private LiquidoOrderData $liquidoOrderData;
    private Config $configData;
    private LiquidoConfigData $liquidoConfig;
    private RefundService $refundService;
    private DataObject $refundInputData;
    private DataObject $refundResultData;
    private String $errorMessage;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        Order $orderInfo,
        Transaction $transaction,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        LiquidoOrderData $liquidoOrderData,
        Config $configData,
        LoggerInterface $logger,
        LiquidoConfigData $liquidoConfig,
        RefundService $refundService
    ) {
        $this->orderRepository = $orderRepository;
        $this->order = $orderInfo;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->configData = $configData;
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;
        $this->refundService = $refundService;
        $this->refundInputData = new DataObject(array());
        $this->refundResultData = new DataObject(array());
        $this->errorMessage = "";
        parent::__construct($context);
    }

    private function validateInputRefundData()
    {
        $adminOrderId = $this->getRequest()->getParam('order_id');
        $objectManager = ObjectManager::getInstance();
        $orderInfo = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($adminOrderId);
        $originalOrderId = $orderInfo->getIncrementId();
        $paymentIdempotencyKey = $this->liquidoSalesOrderHelper
            ->getAlreadyRegisteredIdempotencyKey($originalOrderId);
        if ($paymentIdempotencyKey == null) {
            $this->errorMessage = __('Pagamento não encontrado.');
            return false;
        }
 
        $amount = $orderInfo->getGrandTotal() * 100;
        $refundIdempotencyKey = $this->liquidoOrderData->generateUniqueToken();
        $callbackUrl = $this->liquidoConfig->getCallbackUrl();

        switch ($this->liquidoConfig->getCountry()) {
            case 'BR':
                $currency = Currency::BRL;
                $country = Country::BRAZIL;
                break;
            case 'CO':
                $currency = Currency::COP;
                $country = Country::COLOMBIA;
                break;
        }


        $this->refundInputData = new DataObject([
            "orderId" => $originalOrderId,
            "idempotencyKey" => $refundIdempotencyKey,
            "referenceId" => $paymentIdempotencyKey,
            "amount" => $amount,
            "currency" => $currency,
            "country" => $country,
            "callbackUrl" => $callbackUrl
        ]);

        return true;
    }

    /**
     * Execute action
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Adminhtml Controller ]: Refund Request received.");

        $this->refundResultData = new DataObject([
            "orderId" => null,
            "transferStatus" => null,
            "paymentMethod" => null,
            "hasFailed" => false
        ]);

        $areValidData = $this->validateInputRefundData();
        if (!$areValidData) {
            $this->refundResultData->setData('hasFailed', true);
            $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->refundInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {
            $this->logger->info("[ {$className} Adminhtml Controller ]: Valid input data:", (array) $this->refundInputData);

            $config = new Config(
                [
                    'clientId' => $this->liquidoConfig->getClientId(),
                    'clientSecret' => $this->liquidoConfig->getClientSecret(),
                    'apiKey' => $this->liquidoConfig->getApiKey()
                ],
                $this->liquidoConfig->isProductionModeActived()
            );

            $refundRequest = new RefundRequest([
                "idempotencyKey" => $this->refundInputData->getData('idempotencyKey'),
                "referenceId" => $this->refundInputData->getData('referenceId'),
                "amount" => $this->refundInputData->getData('amount'),
                "currency" => $this->refundInputData->getData('currency'),
                "country" => $this->refundInputData->getData('country'),
                "description" => "Refund Magento 2",
                "callbackUrl" => $this->refundInputData->getData('callbackUrl')
            ]);

            $this->logger->info("[Controler Refund Payload]: ", $refundRequest->toArray());
        
            $refundResponse = $this->refundService->createRefund($config, $refundRequest);
            $this->manageRefundResponse($refundResponse);  
            
            // if (
            //     $refundResponse != null
            //     && property_exists($refundResponse, 'transferStatus')
            //     && $refundResponse->transferStatus != null
            //     && property_exists($refundResponse, 'paymentMethod')
            //     && $refundResponse->transferStatus != null
            // ) {
            //     $orderData = new DataObject(array(
            //         "orderId" => $this->refundInputData->getData('orderId'),
            //         "idempotencyKey" => $this->refundInputData->getData('referenceId'),
            //         "transferStatus" => 'IN_PROGRESS',
            //         "paymentMethod" => $refundResponse->paymentMethod
            //     ));
            // }

            $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->refundResultData);
            $this->logger->info("###################### END ######################");

            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }

    }

    private function manageRefundResponse($refundResponse)
    {
        if (
            $refundResponse != null
            && property_exists($refundResponse, 'transferStatusCode')
            && $refundResponse->transferStatusCode == 200
        ) {
            if ($refundResponse->transferStatus == PayInStatus::IN_PROGRESS) {
                $successMessage = __('Reembolso em processamento, em breve será completado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($refundResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pagamento reembolsado!');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            $this->refundResultData->setData('transferStatus', $refundResponse->transferStatus);
        } else {
            $this->refundResultData->setData('hasFailed', true);

            $errorMsg = "Falha.";
            if (
                $refundResponse != null
                && property_exists($refundResponse, 'status')
                && $refundResponse->status != 200
            ) {
                $errorMsg .= " ($refundResponse->status - $refundResponse->error)";
            } else if (
                $refundResponse != null
                && property_exists($refundResponse, 'transferStatusCode')
                && $refundResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($refundResponse->transferStatusCode - $refundResponse->transferErrorMsg)";
            } else {
                $errorMsg .= " (Erro ao tentar reembolsar o pagamento.)";
            }

            $this->messageManager->addErrorMessage($errorMsg);
        }
    }
}
