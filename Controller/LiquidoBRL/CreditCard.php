<?php

namespace Liquido\PayIn\Controller\LiquidoBRL;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlOrderData;
use \Liquido\PayIn\Service\Brl\LiquidoBrlCreditCardPayInService;
use \Liquido\PayIn\Model\Brl\LiquidoBrlPayInSession;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInStatus;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;
use \Liquido\PayIn\Helper\Brl\LiquidoBrlSalesOrderHelper;

class CreditCard implements ActionInterface
{

    private PageFactory $resultPageFactory;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;
    protected LiquidoBrlPayInSession $payInSession;
    private LiquidoBrlOrderData $liquidoOrderData;
    private LiquidoBrlCreditCardPayInService $creditCardPayInService;
    private LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper;
    private DataObject $creditCardInputData;
    private DataObject $creditCardResultData;
    private RequestInterface $httpRequest;
    private String $errorMessage;

    public function __construct(
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        LiquidoBrlPayInSession $payInSession,
        LiquidoBrlOrderData $liquidoOrderData,
        LiquidoBrlCreditCardPayInService $creditCardPayInService,
        RequestInterface $httpRequest,
        LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->creditCardPayInService = $creditCardPayInService;
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

        $customerCardInstallments = $creditCardFormInputData->getData('card-installments');
        if ($customerCardInstallments == null) {
            $this->errorMessage = __('Erro ao obter o número de parcelas.');
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
            'customerCardInstallments' => $customerCardInstallments,
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
                $creditCardResponse->paymentMethod == LiquidoBrlPaymentMethodType::CREDIT_CARD
                && $creditCardResponse->transferStatus == LiquidoBrlPayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Pagamento em andamento aguardando aprovação.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($creditCardResponse->transferStatus == LiquidoBrlPayInStatus::SETTLED) {
                $successMessage = __('Pagamento já aprovado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            $this->creditCardResultData->setData('amount', $creditCardResponse->amount);

            $this->creditCardResultData->setData('paymentMethod', $creditCardResponse->paymentMethod);

            if ($creditCardResponse->paymentMethod == LiquidoBrlPaymentMethodType::CREDIT_CARD) {
                $this->creditCardResultData->setData(
                    'installments',
                    $creditCardResponse->transferDetails->card->installments
                );
            }
            $this->creditCardResultData->setData('transferStatus', $creditCardResponse->transferStatus);
        } else {
            $this->creditCardResultData->setData('hasFailed', true);

            $errorMsg = "Falha.";
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
                $errorMsg .= " (Erro ao tentar gerar o pagamento)";
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

            $this->creditCardInputData->setData('idempotencyKey', $liquidoIdempotencyKey);

            $creditCardResponse = $this->creditCardPayInService->createCreditCardPayIn(
                $this->creditCardInputData
            );

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
