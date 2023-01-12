<?php

namespace Liquido\PayIn\Controller\Liquido;

use \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
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

use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Util\PaymentFlow;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Model\PayInRequest;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class CreditCard implements ActionInterface
{

    private PageFactory $resultPageFactory;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;
    protected LiquidoPayInSession $payInSession;
    private LiquidoOrderData $liquidoOrderData;
    private PayInService $payInService;
    private LiquidoConfigData $liquidoConfig;
    private LiquidoSalesOrderHelper $liquidoSalesOrderHelper;
    private DataObject $creditCardInputData;
    private DataObject $creditCardResultData;
    private RequestInterface $httpRequest;
    private String $errorMessage;
    private $remoteAddress;

    public function __construct(
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        LiquidoPayInSession $payInSession,
        LiquidoOrderData $liquidoOrderData,
        PayInService $payInService,
        LiquidoConfigData $liquidoConfig,
        RequestInterface $httpRequest,
        LiquidoSalesOrderHelper $liquidoSalesOrderHelper,
        RemoteAddress $remoteAddress
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->payInService = $payInService;
        $this->liquidoConfig = $liquidoConfig;
        $this->httpRequest = $httpRequest;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->creditCardInputData = new DataObject(array());
        $this->creditCardResultData = new DataObject(array());
        $this->errorMessage = "";
    }

    private function validateInputCreditCardData()
    {

        $orderId = $this->liquidoOrderData->getIncrementId();
        if ($orderId == null) {
            $this->errorMessage = __('Erro ao obter o número do pedido.');
            return false;
        }

        $grandTotal = $this->liquidoOrderData->getGrandTotal();
        if ($grandTotal == 0 || null) {
            $this->errorMessage = __('O valor da compra deve ser maior que R$0,00.');
            return false;
        }

        $customerName = $this->liquidoOrderData->getCustomerName();
        if ($customerName == null) {
            $this->errorMessage = __('Erro ao obter o nome do cliente.');
            return false;
        }

        $customerEmail = $this->liquidoOrderData->getCustomerEmail();
        if ($customerEmail == null) {
            $this->errorMessage = __('Erro ao obter o email do cliente.');
            return false;
        }

        $billingAddress = $this->liquidoOrderData->getBillingAddress();
        if ($billingAddress == null) {
            $this->errorMessage = __('Erro ao obter o endereço de cobrança do pedido.');
            return true;
        }

        $streetArray = $billingAddress->getStreet();
        $streetString = $streetArray[0];
        if (count($streetArray) == 2) {
            $streetString .= " - " . $streetArray[1];
        } else if (count($streetArray) == 3) {
            $streetString .= " - " . $streetArray[1] . $streetArray[2];
        }

        $creditCardFormInputData = new DataObject($this->httpRequest->getParams());

        $customerCardName = $creditCardFormInputData->getData('card-name');
        if ($customerCardName == null) {
            $this->errorMessage = __('Erro ao obter o nome do titular do cartão.');
            return false;
        }

        $customerCardNumber = $creditCardFormInputData->getData('card-number');
        if ($customerCardNumber == null) {
            $this->errorMessage = __('Erro ao obter o número do cartão.');
            return false;
        }

        $customerCardExpireDateString = $creditCardFormInputData->getData('card-expire-date');
        $customerCardExpireDateArray = explode("/", $customerCardExpireDateString);
        if ($customerCardExpireDateString == null) {
            $this->errorMessage = __('Erro ao obter a data de expiração do cartão.');
            return false;
        }

        $customerCardCVV = $creditCardFormInputData->getData('card-cvv');
        if ($customerCardCVV == null) {
            $this->errorMessage = __('Erro ao obter o código de verificação do cartão.');
            return false;
        }

        // $customerCardInstallments = $creditCardFormInputData->getData('card-installments');
        // if ($customerCardInstallments == null) {
        //     $this->errorMessage = __('Erro ao obter o número de parcelas.');
        //     return false;
        // }

        $customerDocument = null;
        if ($this->liquidoConfig->getCountry() == 'BR') {
            $customerDocument = $creditCardFormInputData->getData('customer-cpf');
        } elseif ($this->liquidoConfig->getCountry() == 'CO') {
            $customerDocument = $creditCardFormInputData->getData('customer-cc');
        }
        
        if ($customerDocument == null) {
            $this->errorMessage = __('Erro ao obter o documento do cliente.');
            return false;
        }

        $customerIpAddress = $this->remoteAddress->getRemoteAddress();
        if ($customerIpAddress == null) {
            $this->errorMessage = __('Erro ao obter o IP do cliente.');
            return false;
        }

        $this->creditCardInputData = new DataObject(array(
            'orderId' => $orderId,
            'grandTotal' => $grandTotal,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'customerCardName' => $customerCardName,
            'customerCardNumber' => $customerCardNumber,
            'customerCardExpireMonth' => $customerCardExpireDateArray[0],
            'customerCardExpireYear' => $customerCardExpireDateArray[1],
            'customerCardCVV' => $customerCardCVV,
            // 'customerCardInstallments' => $customerCardInstallments,
            'customerDocument' => $customerDocument,
            'customerBillingAddress' => $billingAddress,
            'streetText' => $streetString,
            'customerIpAddress' => $customerIpAddress
        ));

        return true;
    }

    private function manageCreditCardResponse($creditCardResponse)
    {    
        if (
            $creditCardResponse != null
            && property_exists($creditCardResponse, 'transferStatusCode')
            && $creditCardResponse->transferStatusCode == 200
        ) {
            if (
                $creditCardResponse->paymentMethod == PaymentMethod::CREDIT_CARD
                && $creditCardResponse->transferStatus == PayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Pagamento aguardando aprovação.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($creditCardResponse->transferStatus == PayInStatus::SETTLED) {
                $successMessage = __('Pagamento aprovado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            $this->creditCardResultData->setData('amount', $creditCardResponse->amount);

            $this->creditCardResultData->setData('paymentMethod', $creditCardResponse->paymentMethod);

            if ($creditCardResponse->paymentMethod == PaymentMethod::CREDIT_CARD) {
                $this->creditCardResultData->setData(
                    'installments',
                    $creditCardResponse->transferDetails->card->installments
                );
            }
            $this->creditCardResultData->setData('transferStatus', $creditCardResponse->transferStatus);
        } else {
            $this->creditCardResultData->setData('hasFailed', true);

            $errorMsg = __("Falha.");
            if (
                $creditCardResponse != null
                && property_exists($creditCardResponse, 'status')
                && $creditCardResponse->status != 200
            ) {
                $errorMsg .= " ($creditCardResponse->status - $creditCardResponse->error)";
            } else if (
                $creditCardResponse != null
                && property_exists($creditCardResponse, 'transferStatusCode')
                && $creditCardResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($creditCardResponse->transferStatusCode - $creditCardResponse->transferErrorMsg)";
            } else {
                $errorMsg .= __("Erro ao tentar gerar o pagamento");
            }

            $this->messageManager->addErrorMessage($errorMsg);
        }
    }

    public function execute()
    {

        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: CREDIT CARD Request received.");

        /**
         * Data to pass from Controller to Block
         */
        $this->creditCardResultData = new DataObject(array(
            'orderId' => null,
            'amount' => null,
            'installments' => null,
            'transferStatus' => null,
            'paymentMethod' => null,
            'hasFailed' => false
        ));

        $areValidData = $this->validateInputCreditCardData();
        if (!$areValidData) {
            $this->creditCardResultData->setData('hasFailed', true);
            $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->creditCardInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {

            $this->logger->info("[ {$className} Controller ]: Valid input data:", (array) $this->creditCardInputData);

            $orderId = $this->creditCardInputData->getData("orderId");

            $this->creditCardResultData->setData('orderId', $orderId);

            /**
             * Don't generate a new idempotency key if a request was already done successfuly before.
             */
            $liquidoIdempotencyKey = $this->liquidoSalesOrderHelper
                ->getAlreadyRegisteredIdempotencyKey($orderId);
            if ($liquidoIdempotencyKey == null) {
                $liquidoIdempotencyKey = $this->liquidoOrderData->generateUniqueToken();
            }

            $country = $this->liquidoConfig->getCountry();

            $config = new Config(
                [
                    'clientId' => $this->liquidoConfig->getClientId(),
                    'clientSecret' => $this->liquidoConfig->getClientSecret(),
                    'apiKey' => $this->liquidoConfig->getApiKey()
                ],
                $this->liquidoConfig->isProductionModeActived()
            );

            $payload = [
                "idempotencyKey" => $liquidoIdempotencyKey,
                "amount" => $this->creditCardInputData->getData('grandTotal'),
                "paymentMethod" => PaymentMethod::CREDIT_CARD,
                "paymentFlow" => PaymentFlow::DIRECT,
                "callbackUrl" => $this->liquidoConfig->getCallbackUrl(),
                "payer" => [
                    "name" => $this->creditCardInputData->getData("customerName"),
                    "email" => $this->creditCardInputData->getData("customerEmail"),
                    "billingAddress" => [
                        "zipCode" => $this->creditCardInputData->getData("customerBillingAddress")->getPostcode(),
                        "state" => $this->creditCardInputData->getData("customerBillingAddress")->getRegionCode(),
                        "city" => $this->creditCardInputData->getData("customerBillingAddress")->getCity(),
                        "district" => "Unknown",
                        "street" => $this->creditCardInputData->getData("streetText"),
                        "number" => "Unknown",
                        "country" => $this->creditCardInputData->getData("customerBillingAddress")->getCountryId()
                    ]
                ],
                "card" => [
                    "cardHolderName" => $this->creditCardInputData->getData("customerCardName"),
                    "cardNumber" => $this->creditCardInputData->getData("customerCardNumber"),
                    "expirationMonth" => $this->creditCardInputData->getData("customerCardExpireMonth"),
                    "expirationYear" => $this->creditCardInputData->getData("customerCardExpireYear"),
                    "cvc" => $this->creditCardInputData->getData("customerCardCVV")
                ],
                "orderInfo" => [  
                    "orderId" => $orderId,  
                    "shippingInfo" => [ 
                        "name" => $this->creditCardInputData->getData("customerName"),  
                        "phone" => "Unknown",  
                        "email" => $this->creditCardInputData->getData("customerEmail"),
                        "address" => [ 
                            "street" => $this->creditCardInputData->getData("streetText"),
                            "number" => "Unknown",
                            "complement" => "Unknown",
                            "district" => "Unknown",
                            "city" => $this->creditCardInputData->getData("customerBillingAddress")->getCity(),
                            "state" => $this->creditCardInputData->getData("customerBillingAddress")->getRegionCode(),
                            "zipCode" => $this->creditCardInputData->getData("customerBillingAddress")->getPostcode(),
                            "country" => $country
                        ]   
                    ]
                ],
                "description" => "Module Magento 2 Credit Card Request",
                "riskData" => [
                    "ipAddress" => $this->creditCardInputData->getData("customerIpAddress")
                ]
            ];

            if ($country == 'BR') {
                $payload["currency"] = Currency::BRL;
                $payload["country"] = Country::BRAZIL;
                //$payload["installments"] = $this->creditCardInputData->getData("customerCardInstallments");
                $payload["payer"]["document"] = [
                    "documentId" => $this->creditCardInputData->getData("customerDocument"),
                    "type" => "CPF"
                ];
            } elseif ($country == 'CO') {
                $payload["currency"] = Currency::COP;
                $payload["country"] = Country::COLOMBIA;
                //$payload["payer"]["document"] = $this->creditCardInputData->getData("customerDocument");
            }

            $this->logger->info("[Controler Credit Card Payload]: ", $payload);

            $payInRequest = new PayInRequest($payload);
            $creditCardResponse = $this->payInService->createPayIn($config, $payInRequest);
            
            $this->logger->info("[Controler Credit Card Response]: ", (array) $creditCardResponse);

            $this->manageCreditCardResponse($creditCardResponse);

            if (
                $creditCardResponse != null
                && property_exists($creditCardResponse, 'transferStatus')
                && $creditCardResponse->transferStatus != null
                && property_exists($creditCardResponse, 'paymentMethod')
                && $creditCardResponse->transferStatus != null
            ) {
                $orderData = new DataObject(array(
                    "orderId" => $orderId,
                    "idempotencyKey" => $liquidoIdempotencyKey,
                    "transferStatus" => $creditCardResponse->transferStatus,
                    "paymentMethod" => $creditCardResponse->paymentMethod
                ));
                $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
            }
        }

        $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->creditCardResultData);
        $this->logger->info("###################### END ######################");

        $this->payInSession->setData("creditCardResultData", $this->creditCardResultData);

        return $this->resultPageFactory->create();
    }
}
