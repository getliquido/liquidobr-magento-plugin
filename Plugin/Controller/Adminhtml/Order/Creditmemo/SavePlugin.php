<?php

namespace Liquido\PayIn\Plugin\Controller\Adminhtml\Order\Creditmemo;

use \Magento\Framework\DataObject;
use \Magento\Framework\Registry;
use \Magento\Framework\UrlInterface;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\App\ResponseFactory;
use \Magento\Framework\App\Response\RedirectInterface;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Exception\State\InitException;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use \Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Util\MagentoSaleOrderStatus;

use \LiquidoBrl\PayInPhpSdk\Model\RefundRequest;
use \LiquidoBrl\PayInPhpSdk\Service\RefundService;
use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\Brazil\PaymentMethod as BrazilPaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod as CommonPaymentMethod;

class SavePlugin
{
    private DataObject $refundInputData;
    private CreditmemoLoader $creditmemoLoader;

    private LiquidoConfigData $liquidoConfig;
    private LiquidoOrderData $liquidoOrderData;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private RefundService $refundService;

    private $redirect;
    private $objectManager;
    private $request;
    private $logger;
    private $messageManager;
    private $registry;
    private $responseFactory;
    private $url;
    private String $errorMessage;
    private String $redirectionUrl;
    private $orderInfo;

    public function __construct(
        RefundService $refundService,
        CreditmemoLoader $creditmemoLoader,
        LiquidoConfigData $liquidoConfig, 
        LiquidoOrderData $liquidoOrderData,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        RedirectInterface $redirect,
        Http $request,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        Registry $registry,
        ResponseFactory $responseFactory,
        UrlInterface $url
    )
    {
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper; 
        $this->refundService = $refundService;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->request = $request;
        $this->redirect = $redirect;
        $this->logger = $logger;
        $this->errorMessage = "";
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->refundInputData = new DataObject(array());
        $this->objectManager = ObjectManager::getInstance();
        $this->redirectionUrl = $this->url->getUrl($this->redirect->getRefererUrl());
        $orderId = (int) $this->request->getParam('order_id');
        $this->orderInfo = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderId);
    }

    private function validadeRefundInputData()
    {
        $incrementId = $this->orderInfo->getIncrementId();
        $paymentIdempotencyKey = $this->liquidoSalesOrderHelper
            ->getAlreadyRegisteredIdempotencyKey($incrementId);
        if ($paymentIdempotencyKey == null) {
            $this->errorMessage = __('Pagamento não encontrado.');
            return false;
        }

        $this->creditmemoLoader->setOrderId($this->request->getParam('order_id'));
        $this->creditmemoLoader->setCreditmemoId($this->request->getParam('creditmemo_id'));
        $this->creditmemoLoader->setCreditmemo($this->request->getParam('creditmemo'));
        $this->creditmemoLoader->setInvoiceId($this->request->getParam('invoice_id'));
        $creditmemo = $this->creditmemoLoader->load();
        

        $amount = $creditmemo->getGrandTotal() * 100;
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
            "orderId" => $incrementId,
            "idempotencyKey" => $refundIdempotencyKey,
            "referenceId" => $paymentIdempotencyKey,
            "amount" => $amount,
            "currency" => $currency,
            "country" => $country,
            "callbackUrl" => $callbackUrl
        ]);

        return true;
    }

    public function beforeExecute(ActionInterface $subject) 
    {
        $this->logger->info("######################Start Plugin Refund Order######################");

        if($this->canRefund())
        {
            if (!$this->validadeRefundInputData())
            {
                $this->messageManager->addErrorMessage($this->errorMessage);
                $this->logger->warning("[Refund SavePlugin]: Invalid input data:", (array) $this->refundInputData);
                $this->logger->warning("[Refund SavePlugin]: Error message: {$this->errorMessage}");
            }
            else
            {
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

                $refundResponse = null;
                try {
                    $refundResponse = $this->refundService->createRefund($config, $refundRequest);
                    $this->manageRefundResponse($refundResponse);      

                    $this->logger->info("[Refund SavePlugin]: Result data:", (array) $refundResponse);
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());     
                    $this->responseFactory->create()->setRedirect($this->redirectionUrl)->sendResponse();   
                    die();      
                }

                try {
                    if (
                        $refundResponse != null
                        && property_exists($refundResponse, 'transferStatus')
                        && $refundResponse->transferStatus != null
                        && property_exists($refundResponse, 'paymentMethod')
                        && $refundResponse->transferStatus != null
                    ) {
                        $orderData = new DataObject(
                            array(
                                "orderId" => $this->refundInputData->getData("orderId"),
                                "idempotencyKey" => $this->refundInputData->getData('referenceId'),
                                "transferStatus" => "REFUNDED",
                                "paymentMethod" => $refundResponse->paymentMethod
                            )
                        );
                        
                        $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $this->responseFactory->create()->setRedirect($this->redirectionUrl)->sendResponse();
                    die();
                }
            }

            $this->registry->unregister('current_creditmemo');
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
                $successMessage = __('Pagamento reembolsado!');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($refundResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pagamento reembolsado!');
                $this->messageManager->addSuccessMessage($successMessage);
            }

        } else {

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
            $this->responseFactory->create()->setRedirect($this->redirectionUrl)->sendResponse();
            die();
        }
    }

    private function canRefund()
    {
        $orderStatus = $this->orderInfo->getStatus();
        $incrementId = $this->orderInfo->getIncrementId();
        $paymentInfo = $this->liquidoSalesOrderHelper->getPaymentInfoByOrderId($incrementId);

        $bool = false;
        if (
            $orderStatus == MagentoSaleOrderStatus::COMPLETE
                && ($paymentInfo['transfer_status'] == PayInStatus::SETTLED || $paymentInfo['transfer_status'] == PayInStatus::REFUNDED)
                    && ($paymentInfo['payment_method'] == CommonPaymentMethod::CREDIT_CARD || $paymentInfo['payment_method'] == BrazilPaymentMethod::PIX_STATIC_QR)
                        && $this->liquidoConfig->getCountry() == Country::BRAZIL
        )
        {
            $bool = true;
        }

        return $bool;
    }
}
