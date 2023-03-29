<?php

namespace Liquido\PayIn\Controller\LiquidoCO;

use \Magento\Framework\UrlInterface;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\DataObject;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Helper\LiquidoSendEmail;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Util\PaymentFlow;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use \LiquidoBrl\PayInPhpSdk\Model\PayInRequest;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class Cash implements ActionInterface
{
    private PageFactory $resultPageFactory;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;
    protected LiquidoPayInSession $payInSession;
    private LiquidoOrderData $liquidoOrderData;
    private PayInService $payInService;
    private LiquidoConfigData $liquidoConfig;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private DataObject $cashInputData;
    private DataObject $cashResultData;
    private LiquidoSendEmail $sendEmail;
    private StoreManagerInterface $storeManager;
    private UrlInterface $urlInterface;
    private string $errorMessage;

    public function __construct(
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        LiquidoPayInSession $payInSession,
        LiquidoOrderData $liquidoOrderData,
        PayInService $payInService,
        LiquidoConfigData $liquidoConfig,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        LiquidoSendEmail $sendEmail,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->payInService = $payInService;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->cashInputData = new DataObject(array());
        $this->cashResultData = new DataObject(array());
        $this->sendEmail = $sendEmail;
        $this->storeManager = $storeManager;
        $this->urlInterface = $urlInterface;
        $this->errorMessage = "";
        
    }

    private function validateInputCashData()
    {
        $orderId = $this->liquidoOrderData->getIncrementId();
        if ($orderId == null) {
            $this->errorMessage = __('Error al obtener el número de pedido.');
            return false;
        }

        $grandTotal = $this->liquidoOrderData->getGrandTotal();
        if ($grandTotal == 0 || null) {
            $this->errorMessage = __('El valor de la compra debe ser superior a $ 0,00.');
            return false;
        }

        $customerName = $this->liquidoOrderData->getCustomerName();
        if ($customerName == null) {
            $this->errorMessage = __('Error al obtener el nombre del cliente.');
            return false;
        }

        $customerEmail = $this->liquidoOrderData->getCustomerEmail();
        if ($customerEmail == null) {
            $this->errorMessage = __('Error al obtener el correo electrónico del cliente.');
            return false;
        }

        $dateDeadline = date('Y-m-d', strtotime('+2 days', time()));

        $this->cashInputData = new DataObject(
            array(
                'orderId' => $orderId,
                'grandTotal' => $grandTotal,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'expirationDate' => $dateDeadline
            )
        );

        return true;
    }

    private function manageCashResponse($cashResponse)
    {
        if (
            $cashResponse != null
            && property_exists($cashResponse, 'transferStatusCode')
            && $cashResponse->transferStatusCode == 200
        ) {
            if (
                $cashResponse->paymentMethod == PaymentMethod::CASH
                && $cashResponse->transferStatus == PayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Referencia de pago generada con êxito');
                // $this->messageManager->addSuccessMessage($successMessage);
                $this->cashResultData->setData('successMessage', $successMessage);
            }

            if ($cashResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pago aceptado.');
                // $this->messageManager->addSuccessMessage($successMessage);
                $this->cashResultData->setData('successMessage', $successMessage);
            }

            $this->cashResultData->setData('paymentMethod', $cashResponse->paymentMethod);

            if ($cashResponse->paymentMethod == PaymentMethod::CASH) {
                $this->cashResultData->setData('cashCode', $cashResponse->transferDetails->payCash->referenceNumber);
            }

            $this->cashResultData->setData('transferStatus', $cashResponse->transferStatus);
        } else {

            $this->cashResultData->setData('hasFailed', true);

            $errorMsg = "Falla.";

            if (
                $cashResponse != null
                && property_exists($cashResponse, 'status')
                && $cashResponse->status != 200
            ) {
                $errorMsg .= " ($cashResponse->status - $cashResponse->error)";
            } else if (
                $cashResponse != null
                && property_exists($cashResponse, 'transferStatusCode')
                && $cashResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($cashResponse->transferStatusCode - $cashResponse->transferErrorMsg)";
            } else {
                $errorMsg .= " (Error al intentar generar el pago)";
            }

            // $this->messageManager->addErrorMessage($errorMsg);
            $this->cashResultData->setData('errorMessage', $errorMsg);
        }
    }

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: CASH Request received.");

        /**
         * Data to pass from Controller to Block
         */
        $this->cashResultData = new DataObject(
            array(
                'orderId' => null,
                'cashCode' => null,
                'transferStatus' => null,
                'paymentMethod' => null,
                'hasFailed' => false,
                'errorMessage' => null,
                'successMessage' => null
            )
        );

        $areValidData = $this->validateInputCashData();
        if (!$areValidData) {
            $this->cashResultData->setData('hasFailed', true);
            $this->cashResultData->setData('errorMessage', $this->errorMessage);
            // $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->cashInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {

            $this->logger->info("[ {$className} Controller ]: Valid input data:", (array) $this->cashInputData);

            $orderId = $this->cashInputData->getData("orderId");
            $this->cashResultData->setData('orderId', $orderId);

            /**
             * Don't generate a new idempotency key if a request was already done successfuly before.
             */
            $liquidoIdempotencyKey = $this->liquidoSalesOrderHelper
                ->getAlreadyRegisteredIdempotencyKey($orderId);

            if ($liquidoIdempotencyKey == null) {
                $liquidoIdempotencyKey = $this->liquidoOrderData->generateUniqueToken();
            }

            $this->logger->info("Idempotencykey {$liquidoIdempotencyKey}");

            $config = new Config(
                [
                    'clientId' => $this->liquidoConfig->getClientId(),
                    'clientSecret' => $this->liquidoConfig->getClientSecret(),
                    'apiKey' => $this->liquidoConfig->getApiKey()
                ],
                $this->liquidoConfig->isProductionModeActived()
            );

            $payin = [
                "idempotencyKey" => $liquidoIdempotencyKey,
                "amount" => $this->cashInputData->getData('grandTotal'),
                "currency" => Currency::COP,
                "country" => Country::COLOMBIA,
                "paymentMethod" => PaymentMethod::CASH,
                "paymentFlow" => PaymentFlow::DIRECT,
                "callbackUrl" => $this->liquidoConfig->getCallbackUrl(),
                "payer" => [
                    "name" => $this->cashInputData->getData('customerName'),
                    "email" => $this->cashInputData->getData('customerEmail')
                ],
                "expirationDate" => $this->cashInputData->getData('expirationDate'),
                "description" => "Module Magento 2 Colombia, Cash Request"
            ];

            $this->logger->info("PayIn: ", $payin);

            $payInRequest = new PayInRequest($payin);

            try {
                $cashResponse = $this->payInService->createPayIn($config, $payInRequest);
                $this->manageCashResponse($cashResponse);
            } catch (\Exception $e) {
                $this->cashResultData->setData('hasFailed', true);
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            try {
                if (
                    $cashResponse != null
                    && property_exists($cashResponse, 'transferStatus')
                    && $cashResponse->transferStatus != null
                    && property_exists($cashResponse, 'paymentMethod')
                    && $cashResponse->paymentMethod != null
                ) {
                    $orderData = new DataObject(
                        array(
                            "orderId" => $orderId,
                            "idempotencyKey" => $liquidoIdempotencyKey,
                            "transferStatus" => $cashResponse->transferStatus,
                            "paymentMethod" => $cashResponse->paymentMethod
                        )
                    );
                    $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
                }
            } catch (\Exception $e) {
                $this->cashResultData->setData('hasFailed', true);
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            if (!$this->cashResultData->getData('hasFailed')) {
                $amount = $this->cashInputData->getData('grandTotal') / 100;
                $params = array(
                    'name' => $this->cashInputData->getData('customerName'),
                    'email' => $this->cashInputData->getData('customerEmail'),
                    'cashCode' => $this->cashResultData->getData('cashCode'),
                    'expiration' => date('d/m/Y', strtotime($this->cashInputData->getData('expirationDate'))),
                    'amount' => number_format($amount, 0, ',', '.'),
                    'orderId' => $this->cashInputData->getData('orderId'),
                    'storeName' => strtoupper($this->storeManager->getStore()->getName()),
                    'storeURL' => $this->urlInterface->getUrl()
                );
                $this->sendEmail->sendEmail($params);
            }
        }

        $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->cashResultData);
        $this->logger->info("###################### END ######################");

        $this->payInSession->setData("cashResultData", $this->cashResultData);

        $this->logger->info("PayInSession Result Data: ", (array) $this->payInSession->getData("cashResultData"));

        return $this->resultPageFactory->create();
    }
}