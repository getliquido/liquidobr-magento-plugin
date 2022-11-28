<?php

namespace Liquido\PayIn\Controller\LiquidoCO;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Helper\LiquidoSalesOrderHelper;
use \Liquido\PayIn\Helper\LiquidoConfigData;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Util\PaymentFlow;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use \LiquidoBrl\PayInPhpSdk\Model\PayInRequest;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class Pse implements ActionInterface
{
    private PageFactory $resultPageFactory;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;
    protected LiquidoPayInSession $payInSession;
    private LiquidoOrderData $liquidoOrderData;
    private PayInService $payInService;
    private LiquidoConfigData $liquidoConfig;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private DataObject $pseInputData;
    private DataObject $pseResultData;
    private RequestInterface $httpRequest;
    private String $errorMessage;

    public function __construct(
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        LiquidoPayInSession $payInSession,
        LiquidoOrderData $liquidoOrderData,
        PayInService $payInService,
        LiquidoConfigData $liquidoConfig,
        RequestInterface $httpRequest,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->payInService = $payInService;
        $this->liquidoConfig = $liquidoConfig;
        $this->httpRequest = $httpRequest;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->pseInputData = new DataObject(array());
        $this->pseResultData = new DataObject(array());
        $this->errorMessage = "";
    }

    private function validateInputPseData()
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

        $pseFormInputData = new DataObject($this->httpRequest->getParams());

        $customerDocType = $pseFormInputData->getData('customer-doc-type');
        if ($customerDocType == null) {
            $this->errorMessage = __('Error al obtener el tipo de documento.');
            return false;
        }

        $customerDocNumber = $pseFormInputData->getData('customer-doc-number');
        if ($customerDocNumber == null) {
            $this->errorMessage = __('Error al obtener el número de documento.');
            return false;
        }

        $customerPersonType = $pseFormInputData->getData('customer-person-type');
        if ($customerPersonType == null) {
            $this->errorMessage = __('Error al obtener el tipo de persona.');
            return false;
        }

        $customerFinancialInstitution = $pseFormInputData->getData('customer-financial-institution');
        if ($customerFinancialInstitution == null) {
            $this->errorMessage = __('Error al obtener Institución Financiera.');
            return false;
        }

        $this->pseInputData = new DataObject(array(
            'orderId' => $orderId,
            'grandTotal' => $grandTotal,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'customerDocType' => $customerDocType,
            'customerDocNumber' => $customerDocNumber,
            'customerPersonType' => $customerPersonType,
            'customerFinancialInstitution' => $customerFinancialInstitution
        ));

        return true;
    }

    private function managePseResponse($pseResponse)
    {
        if (
            $pseResponse != null
            && property_exists($pseResponse, 'transferStatusCode')
            && $pseResponse->transferStatusCode == 200
        ) {
            if (
                $pseResponse->paymentMethod == PaymentMethod::PSE
                && $pseResponse->transferStatus == PayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Link PSE generado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($pseResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pago aceptado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            $this->pseResultData->setData('paymentMethod', $pseResponse->paymentMethod);

            if ($pseResponse->paymentMethod == PaymentMethod::PSE) {
                if (!$this->liquidoConfig->isProductionModeActived()){
                    $this->pseResultData->setData('pseLink', '');
                } else {
                    $this->pseResultData->setData('pseLink', $pseResponse->transferDetails->pse->paymentUrl);
                }
            }

            $this->pseResultData->setData('transferStatus', $pseResponse->transferStatus);
        } else {
            $this->pseResultData->setData('hasFailed', true);

            $errorMsg = "Falla.";
            if (
                $pseResponse != null
                && property_exists($pseResponse, 'status')
                && $pseResponse->status != 200
            ) {
                $errorMsg .= " ($pseResponse->status - $pseResponse->error)";
            } else if (
                $pseResponse != null
                && property_exists($pseResponse, 'transferStatusCode')
                && $pseResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($pseResponse->transferStatusCode - $pseResponse->transferErrorMsg)";
            } else {
                $errorMsg .= " (Error al intentar generar el pago)";
            }

            $this->messageManager->addErrorMessage($errorMsg);
        }
    }

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: PSE Request received.");

        /**
         * Data to pass from Controller to Block
         */
        $this->pseResultData = new DataObject(array(
            'orderId' => null,
            'pseLink' => null,
            'transferStatus' => null,
            'paymentMethod' => null,
            'hasFailed' => false
        ));

        $areValidData = $this->validateInputPseData();
        if (!$areValidData) {
            $this->pseResultData->setData('hasFailed', true);
            $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->pseInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {

            $this->logger->info("[ {$className} Controller ]: Valid input data:", (array) $this->pseInputData);

            $orderId = $this->pseInputData->getData("orderId");

            $this->pseResultData->setData('orderId', $orderId);

            /**
             * Don't generate a new idempotency key if a request was already done successfuly before.
             */
            $liquidoIdempotencyKey = $this->liquidoSalesOrderHelper
                ->getAlreadyRegisteredIdempotencyKey($orderId);
            if ($liquidoIdempotencyKey == null) {
                $liquidoIdempotencyKey = $this->liquidoOrderData->generateUniqueToken();
            }

            $config = new Config(
                [
                    'clientId' => $this->liquidoConfig->getClientId(),
                    'clientSecret' => $this->liquidoConfig->getClientSecret(),
                    'apiKey' => $this->liquidoConfig->getApiKey()
                ],
                $this->liquidoConfig->isProductionModeActived()
            );

            $payInRequest = new PayInRequest([
                "idempotencyKey" => $liquidoIdempotencyKey,
                "amount" => $this->pseInputData->getData('grandTotal'),
                "currency" => Currency::COP,
                "country" => Country::COLOMBIA,
                "paymentMethod" => PaymentMethod::PSE,
                "paymentFlow" => PaymentFlow::DIRECT,
                "callbackUrl" => $this->liquidoConfig->getCallbackUrl(),
                "pse" => [
                    "personType" => $this->pseInputData->getData('customerPersonType'),
                    "financialInstitutionCode" => $this->pseInputData->getData('customerFinancialInstitution')
                ],
                "payer" => [
                    "name" => $this->pseInputData->getData('customerName'),
                    "email" => $this->pseInputData->getData('customerEmail'),
                    "document" => [
                        "documentId" => $this->pseInputData->getData('customerDocNumber'),
                        "type" => $this->pseInputData->getData('customerDocType')
                    ]
                ],
                "description" => "Module Magento 2 Colombia, PSE Request"
            ]);

            $pseResponse = $this->payInService->createPayIn($config, $payInRequest);

            $this->managePseResponse($pseResponse);
            if (
                $pseResponse != null
                && property_exists($pseResponse, 'transferStatus')
                && $pseResponse->transferStatus != null
                && property_exists($pseResponse, 'paymentMethod')
                && $pseResponse->paymentMethod != null
            ) {
                $orderData = new DataObject(array(
                    "orderId" => $orderId,
                    "idempotencyKey" => $liquidoIdempotencyKey,
                    "transferStatus" => $pseResponse->transferStatus,
                    "paymentMethod" => $pseResponse->paymentMethod
                ));
                $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
            }
        }

        $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->pseResultData);
        $this->logger->info("###################### END ######################");

        $this->payInSession->setData("pseResultData", $this->pseResultData);

        return $this->resultPageFactory->create();
    }
}