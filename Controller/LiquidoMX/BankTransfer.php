<?php

namespace Liquido\PayIn\Controller\LiquidoMX;

use \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use \Magento\Framework\UrlInterface;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\DataObject;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Model\LiquidoPayInSession;

use \LiquidoBrl\PayInPhpSdk\Util\Mexico\PaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Util\PaymentFlow;
use \LiquidoBrl\PayInPhpSdk\Model\PayInRequest;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class BankTransfer implements ActionInterface
{    
    private PageFactory $resultPageFactory;
    private UrlInterface $urlInterface;
    private ManagerInterface $messageManager;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;
    private DataObject $bankTransferInputData;
    private DataObject $bankTransferResultData;

    protected LiquidoPayInSession $payInSession;
    private LiquidoOrderData $liquidoOrderData;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private LiquidoConfigData $liquidoConfig;
    private string $errorMessage;
    private $remoteAddress;

    private PayInService $payInService;

    public function __construct(
        PageFactory $resultPageFactory,
        UrlInterface $urlInterface,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        LiquidoOrderData $liquidoOrderData,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        LiquidoConfigData $liquidoConfig,
        RemoteAddress $remoteAddress,
        PayInService $payInService,
        LiquidoPayInSession $payInSession
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->urlInterface = $urlInterface;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->errorMessage = "";
        $this->bankTransferInputData = new DataObject(array());
        $this->bankTransferResultData = new DataObject(array());
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->liquidoConfig = $liquidoConfig;
        $this->remoteAddress = $remoteAddress;
        $this->payInService = $payInService;
        $this->payInSession = $payInSession;
    }

    private function validateInputBankTransferData()
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

        $this->bankTransferInputData = new DataObject(
            array(
                'orderId' => $orderId,
                'grandTotal' => $grandTotal,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail
            )
        );

        return true;
    }

    private function manageBankTransferResponse($bankTransferResponse)
    {
        if (
            $bankTransferResponse != null
            && property_exists($bankTransferResponse, 'transferStatusCode')
            && $bankTransferResponse->transferStatusCode == 200
        ) {
            if (
                $bankTransferResponse->paymentMethod == PaymentMethod::BANK_TRANSFER
                && $bankTransferResponse->transferStatus == PayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Transferencia en proceso.');
                // $this->messageManager->addSuccessMessage($successMessage);
                $this->bankTransferResultData->setData('successMessage', $successMessage);
            }

            if ($bankTransferResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pago realizado con éxito.');
                // $this->messageManager->addSuccessMessage($successMessage);
                $this->bankTransferResultData->setData('successMessage', $successMessage);
            }

            $this->bankTransferResultData->setData('paymentMethod', $bankTransferResponse->paymentMethod);

            if ($bankTransferResponse->paymentMethod == PaymentMethod::BANK_TRANSFER) {
                $this->bankTransferResultData->setData('amount', $bankTransferResponse->amount);
                $this->bankTransferResultData->setData('beneficiaryName', $bankTransferResponse->transferDetails->bankTransfer->bankAccountInfo->beneficiaryName);
                $this->bankTransferResultData->setData('bankName', $bankTransferResponse->transferDetails->bankTransfer->bankAccountInfo->bankName);
                $this->bankTransferResultData->setData('bankAccountType', $bankTransferResponse->transferDetails->bankTransfer->bankAccountInfo->bankAccountType);
                $this->bankTransferResultData->setData('bankAccountNumber', $bankTransferResponse->transferDetails->bankTransfer->bankAccountInfo->bankAccountNumber);
            }

            $this->bankTransferResultData->setData('transferStatus', $bankTransferResponse->transferStatus);
        } else {

            $this->bankTransferResultData->setData('hasFailed', true);

            $errorMsg = "Falla.";

            if (
                $bankTransferResponse != null
                && property_exists($bankTransferResponse, 'status')
                && $bankTransferResponse->status != 200
            ) {
                $errorMsg .= " ($bankTransferResponse->status - $bankTransferResponse->error)";
            } else if (
                $bankTransferResponse != null
                && property_exists($bankTransferResponse, 'transferStatusCode')
                && $bankTransferResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($bankTransferResponse->transferStatusCode - $bankTransferResponse->transferErrorMsg)";
            } else {
                $errorMsg .= " (Error al intentar generar el pago)";
            }

            // $this->messageManager->addErrorMessage($errorMsg);
            $this->bankTransferResultData->setData('errorMessage', $errorMsg);
        }
    }

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: Bank Transfer Request received.");

        /**
         * Data to pass from Controller to Block
         */
        $this->bankTransferResultData = new DataObject(
            array(
                'orderId' => null,
                'amount' => null,
                'beneficiaryName' => null,
                'bankName' => null,
                'bankAccountType' => null,
                'bankAccountNumber' =>null,
                'beneficiaryName' => null,
                'transferStatus' => null,
                'paymentMethod' => null,
                'hasFailed' => false,
                'errorMessage' => null,
                'successMessage' => null
            )
        );

        $areValidData = $this->validateInputBankTransferData();
        if (!$areValidData) {
            $this->bankTransferResultData->setData('hasFailed', true);
            $this->bankTransferResultData->setData('errorMessage', $this->errorMessage);
            // $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->bankTransferInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {
            $this->logger->info("[ {$className} Controller ]: Valid input data:", (array) $this->bankTransferInputData);

            $orderId = $this->bankTransferInputData->getData("orderId");
            $this->bankTransferResultData->setData('orderId', $orderId);

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
                "amount" => $this->bankTransferInputData->getData('grandTotal'),
                "currency" => Currency::MXN,
                "country" => Country::MEXICO,
                "paymentMethod" => PaymentMethod::BANK_TRANSFER,
                "paymentFlow" => PaymentFlow::DIRECT,
                "callbackUrl" => $this->liquidoConfig->getCallbackUrl(),
                "payer" => [
                    "name" => $this->bankTransferInputData->getData('customerName'),
                    "email" => $this->bankTransferInputData->getData('customerEmail')
                ],
                "orderInfo" => [
                    "orderId" => $this->bankTransferInputData->getData('orderId')
                ],
                "riskData" => [
                    "ipAddress" => $this->remoteAddress->getRemoteAddress()
                ],
                "description" => "Module Magento 2 Mexico, Bank Transfer Request"
            ];

            $this->logger->info("PayIn: ", $payin);

            $payInRequest = new PayInRequest($payin);

            try {
                $bankTransferResponse = $this->payInService->createPayIn($config, $payInRequest);
                $this->manageBankTransferResponse($bankTransferResponse);
            } catch (\Exception $e) {
                $this->bankTransferResultData->setData('hasFailed', true);
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            try {
                if (
                    $bankTransferResponse != null
                    && property_exists($bankTransferResponse, 'transferStatus')
                    && $bankTransferResponse->transferStatus != null
                    && property_exists($bankTransferResponse, 'paymentMethod')
                    && $bankTransferResponse->paymentMethod != null
                ) {
                    $orderData = new DataObject(
                        array(
                            "orderId" => $orderId,
                            "idempotencyKey" => $liquidoIdempotencyKey,
                            "transferStatus" => $bankTransferResponse->transferStatus,
                            "paymentMethod" => $bankTransferResponse->paymentMethod
                        )
                    );
                    $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
                }
            } catch (\Exception $e) {
                $this->bankTransferResultData->setData('hasFailed', true);
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            
        }

        $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->bankTransferResultData);
        $this->logger->info("###################### END ######################");

        $this->payInSession->setData("bankTransferResultData", $this->bankTransferResultData);

        $this->logger->info("PayInSession Result Data: ", (array) $this->payInSession->getData("bankTransferResultData"));

        return $this->resultPageFactory->create();
    }
}