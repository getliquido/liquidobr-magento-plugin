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
use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

use \Liquido\PayIn\Helper\LiquidoCreditmemoHelper;
use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Logger\Logger;
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
    private DataObject $creditmemoData;
    private CreditmemoLoader $creditmemoLoader;

    private LiquidoConfigData $liquidoConfig;
    private LiquidoOrderData $liquidoOrderData;
    private LiquidoCreditmemoHelper $liquidoCreditmemoHelper;
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
        LiquidoCreditmemoHelper $liquidoCreditmemoHelper,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        RedirectInterface $redirect,
        Http $request,
        Logger $logger,
        ManagerInterface $messageManager,
        Registry $registry,
        ResponseFactory $responseFactory,
        UrlInterface $url
    )
    {
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->liquidoCreditmemoHelper = $liquidoCreditmemoHelper;
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
        $this->creditmemoData = new DataObject(array()); 
        $this->objectManager = ObjectManager::getInstance();
        $this->redirectionUrl = $this->url->getUrl($this->redirect->getRefererUrl());
        $orderId = $this->request->getParam('order_id');
        $this->orderInfo = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByAttribute('entity_id', $orderId); 
    }

    private function validadeRefundInputData()
    {
        $incrementId = $this->orderInfo->getIncrementId();
        $referenceId = $this->liquidoSalesOrderHelper
            ->getAlreadyRegisteredIdempotencyKey($incrementId);
        if ($referenceId == null) {
            $this->errorMessage = __('Pagamento não encontrado.');
            return false;
        }

        $this->creditmemoLoader->setOrderId($this->request->getParam('order_id'));
        $this->creditmemoLoader->setCreditmemoId($this->request->getParam('creditmemo_id'));
        $this->creditmemoLoader->setCreditmemo($this->request->getParam('creditmemo'));
        $this->creditmemoLoader->setInvoiceId($this->request->getParam('invoice_id'));
        $creditmemo = $this->creditmemoLoader->load();
    
        $amount = $creditmemo->getGrandTotal() * 100;
        $creditmemoId = $this->getIncrementId();
        $idempotencyKey = $this->liquidoOrderData->generateUniqueToken();
        $callbackUrl = $this->liquidoConfig->getCallbackUrl();

        $foundLiquidoSalesOrder = $this->liquidoSalesOrderHelper->findLiquidoSalesOrderByOrderId($incrementId);
        $paymentMethod = $foundLiquidoSalesOrder->getData('payment_method');

        switch ($this->liquidoConfig->getCountry()) {
            case 'BR':
                $currency = Currency::BRL;
                $country = Country::BRAZIL;
                break;
            case 'CO':
                $currency = Currency::COP;
                $country = Country::COLOMBIA;
                break;
            case 'MX':
                $currency = Currency::MXN;
                $country = Country::MEXICO;
                break;
        }

        $this->refundInputData = new DataObject([
            "orderId" => $incrementId,
            "creditmemoId" => $creditmemoId,
            "idempotencyKey" => $idempotencyKey,
            "referenceId" => $referenceId,
            "amount" => $amount,
            "currency" => $currency,
            "country" => $country,
            "callbackUrl" => $callbackUrl,
            "transferStatus" => null,
            "paymentMethod" => $paymentMethod
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

                $this->logger->info("************* Refund Payload ************", (array) $refundRequest->toArray());

                $refundResponse = null;
                try {
                    $refundResponse = $this->refundService->createRefund($config, $refundRequest);
                    $this->manageRefundResponse($refundResponse);      

                    $this->logger->info("[Refund SavePlugin]: Result data:", (array) $refundResponse);
                } catch (\Exception $e) {
                    $creditmemoData = new DataObject(
                        array(
                            "orderId" => $this->refundInputData->getData("orderId"),
                            "creditmemoId" => '',
                            "idempotencyKey" => $this->refundInputData->getData("idempotencyKey"),
                            "referenceId" => $this->refundInputData->getData("referenceId"),
                            "transferStatus" => PayInStatus::FAILED,
                            "json" => json_encode($this->request->getParam('creditmemo'))
                        )
                    );

                    $this->logger->info("************* Refund Exception beforeExecute************", (array) $creditmemoData);
                    
                    $this->liquidoCreditmemoHelper->createOrUpdateLiquidoCreditmemo($creditmemoData);

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
                $successMessage = __('Reembolso em processamento, em breve será concluído.');
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

            $creditmemoData = new DataObject(
                array(
                    "orderId" => $this->refundInputData->getData("orderId"),
                    "creditmemoId" => '',
                    "idempotencyKey" => $this->refundInputData->getData("idempotencyKey"),
                    "referenceId" => $this->refundInputData->getData("referenceId"),
                    "transferStatus" => PayInStatus::FAILED,
                    "json" => json_encode($this->request->getParam('creditmemo'))
                )
            );

            $this->logger->info("************* Refund Exception manageRefundResponse************", (array) $creditmemoData);
            
            $this->liquidoCreditmemoHelper->createOrUpdateLiquidoCreditmemo($creditmemoData);

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
        ) 
        {
            switch ($this->liquidoConfig->getCountry()) {
                case Country::BRAZIL:
                    if($paymentInfo['payment_method'] != BrazilPaymentMethod::BOLETO)
                    {
                        $bool = true;
                    }
                    break;
                case Country::COLOMBIA:
                    if($paymentInfo['payment_method'] == CommonPaymentMethod::CREDIT_CARD)
                    {
                        $bool = true;
                    }
                    break;
                case Country::MEXICO:
                    if($paymentInfo['payment_method'] != CommonPaymentMethod::CASH)
                    {
                        $bool = true;
                    }
                    break;
                default:
                    $bool = false;
                    break;
            }
        }

        return $bool;
    }

    public function getIncrementId()
    {
        $this->logger->info("############getIncrementId#############");

        $creditmemoRepositoryInterface = $this->objectManager->get(CreditmemoRepositoryInterface::class);
        $searchCriteriaBuilder = $this->objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        
        $searchCriteria = $searchCriteriaBuilder->addFilter('order_id', $this->request->getParam('order_id'))->create();
        $creditmemos = $creditmemoRepositoryInterface->getList($searchCriteria);
        $creditmemoItems = $creditmemos->getItems();

        $creditmemoList = array();
        foreach ($creditmemoItems as $creditmemoItem)
        {
            $creditmemoList []= $creditmemoItem->getIncrementId();
        }

        $creditmemoId = end($creditmemoList);

        return $creditmemoId;
    }

    public function afterExecute(ActionInterface $subject, $result)
    {
        $this->logger->info("############ AFTER EXECUTE #############", (array) $this->creditmemoData);

        try{
            $creditmemoId = $this->getIncrementId();
        
            $creditmemoData = new DataObject(
                array(
                    "orderId" => $this->refundInputData->getData("orderId"),
                    "creditmemoId" => $creditmemoId,
                    "idempotencyKey" => $this->refundInputData->getData("idempotencyKey"),
                    "referenceId" => $this->refundInputData->getData("referenceId"),
                    "transferStatus" => PayInStatus::IN_PROGRESS,
                    "json" => json_encode($this->request->getParam('creditmemo'))
                )
            );
            
            $this->liquidoCreditmemoHelper->createOrUpdateLiquidoCreditmemo($creditmemoData);

            //$foundLiquidoSalesOrder = $this->liquidoSalesOrderHelper->findLiquidoSalesOrderByOrderId($this->refundInputData->getData("orderId"));
            //$this->liquidoSalesOrderHelper->updateLiquidoSalesOrderStatus($foundLiquidoSalesOrder, PayInStatus::REFUNDED);
            return $result;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
