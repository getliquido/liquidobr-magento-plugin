<?php

namespace Liquido\PayIn\Controller\LiquidoBRL;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlOrderData;
use \Liquido\PayIn\Service\Brl\LiquidoBrlBoletoPayInService;
use \Liquido\PayIn\Model\Brl\LiquidoBrlPayInSession;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInStatus;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;
use \Liquido\PayIn\Helper\Brl\LiquidoBrlSalesOrderHelper;

class Boleto implements ActionInterface
{
    private PageFactory $resultPageFactory;
    private ManagerInterface $messageManager;
    private LoggerInterface $logger;
    protected LiquidoBrlPayInSession $payInSession;
    private LiquidoBrlOrderData $liquidoOrderData;
    private LiquidoBrlBoletoPayInService $boletoPayInService;
    private LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper;
    private DataObject $boletoInputData;
    private DataObject $boletoResultData;
    private RequestInterface $httpRequest;
    private String $errorMessage;

    /**
     * Boleto Controller constructor
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        LiquidoBrlPayInSession $payInSession,
        LiquidoBrlOrderData $liquidoOrderData,
        LiquidoBrlBoletoPayInService $boletoPayInService,
        RequestInterface $httpRequest,
        LiquidoBrlSalesOrderHelper $liquidoSalesOrderHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->boletoPayInService = $boletoPayInService;
        $this->httpRequest = $httpRequest;
        $this->liquidoSalesOrderHelper = $liquidoSalesOrderHelper;
        $this->boletoInputData = new DataObject(array());
        $this->boletoResultData = new DataObject(array());
        $this->errorMessage = "";
    }

    private function validateInputBoletoData()
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

        $boletoFormInputData = new DataObject($this->httpRequest->getParams());

        $customerCpf = $boletoFormInputData->getData('customer-cpf');
        if ($customerCpf == null) {
            $this->errorMessage = __('Erro ao obter o CPF do cliente.');
            return false;
        }

        // Boleto date expiration (timestamp)
        $dateDeadline = date('Y-m-d H:i:s', strtotime('+5 days', time()));
        $timestampDeadline = strtotime($dateDeadline);

        $this->boletoInputData = new DataObject(array(
            'orderId' => $orderId,
            'grandTotal' => $grandTotal,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'customerCpf' => $customerCpf,
            'customerBillingAddress' => $billingAddress,
            'streetText' => $streetString,
            'paymentDeadline' => $timestampDeadline
        ));

        return true;
    }

    private function manageBoletoResponse($boletoResponse)
    {
        if (
            $boletoResponse != null
            && property_exists($boletoResponse, 'transferStatusCode')
            && $boletoResponse->transferStatusCode == 200
        ) {
            if (
                $boletoResponse->paymentMethod == LiquidoBrlPaymentMethodType::BOLETO
                && $boletoResponse->transferStatus == LiquidoBrlPayInStatus::IN_PROGRESS
            ) {
                $successMessage = __('Boleto gerado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            if ($boletoResponse->transferStatus == LiquidoBrlPayInStatus::SETTLED) {
                $successMessage = __('Pagamento já aprovado.');
                $this->messageManager->addSuccessMessage($successMessage);
            }

            $this->boletoResultData->setData('paymentMethod', $boletoResponse->paymentMethod);

            if ($boletoResponse->paymentMethod == LiquidoBrlPaymentMethodType::BOLETO) {
                $this->boletoResultData->setData(
                    'boletoDigitalLine',
                    $boletoResponse->transferDetails->boleto->digitalLine
                );
            }

            $this->boletoResultData->setData('transferStatus', $boletoResponse->transferStatus);
        } else {
            $this->boletoResultData->setData('hasFailed', true);

            $errorMsg = "Falha.";
            if (
                $boletoResponse != null
                && property_exists($boletoResponse, 'status')
                && $boletoResponse->status != 200
            ) {
                $errorMsg .= " ($boletoResponse->status - $boletoResponse->error)";
            } else if (
                $boletoResponse != null
                && property_exists($boletoResponse, 'transferStatusCode')
                && $boletoResponse->transferStatusCode != 200
            ) {
                $errorMsg .= " ($boletoResponse->transferStatusCode - $boletoResponse->transferErrorMsg)";
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
        $this->logger->info("[ {$className} Controller ]: BOLETO Request received.");

        /**
         * Data to pass from Controller to Block
         */
        $this->boletoResultData = new DataObject(array(
            'orderId' => null,
            'boletoDigitalLine' => null,
            'boletoUrl' => null,
            'transferStatus' => null,
            'paymentMethod' => null,
            'hasFailed' => false
        ));

        $areValidData = $this->validateInputBoletoData();
        if (!$areValidData) {
            $this->boletoResultData->setData('hasFailed', true);
            $this->messageManager->addErrorMessage($this->errorMessage);
            $this->logger->warning("[ {$className} Controller ]: Invalid input data:", (array) $this->boletoInputData);
            $this->logger->warning("[ {$className} Controller ]: Error message: {$this->errorMessage}");
        } else {

            $this->logger->info("[ {$className} Controller ]: Valid input data:", (array) $this->boletoInputData);

            $orderId = $this->boletoInputData->getData("orderId");

            $this->boletoResultData->setData('orderId', $orderId);

            /**
             * Don't generate a new idempotency key if a request was already done successfuly before.
             */
            $liquidoIdempotencyKey = $this->liquidoSalesOrderHelper
                ->getAlreadyRegisteredIdempotencyKey($orderId);
            if ($liquidoIdempotencyKey == null) {
                $liquidoIdempotencyKey = $this->liquidoOrderData->generateUniqueToken();
            }

            $this->boletoInputData->setData('idempotencyKey', $liquidoIdempotencyKey);

            $boletoResponse = $this->boletoPayInService->createLiquidoBoletoPayIn(
                $this->boletoInputData
            );

            $this->manageBoletoResponse($boletoResponse);

            if (
                $boletoResponse != null
                && property_exists($boletoResponse, 'transferStatus')
                && $boletoResponse->transferStatus != null
                && property_exists($boletoResponse, 'paymentMethod')
                && $boletoResponse->transferStatus != null
            ) {
                $boletoUrl = $this->boletoPayInService->getLiquidoBoletoPdfUrl(
                    $liquidoIdempotencyKey
                );

                $this->boletoResultData->setData('boletoUrl', $boletoUrl);

                $orderData = new DataObject(array(
                    "orderId" => $orderId,
                    "idempotencyKey" => $liquidoIdempotencyKey,
                    "transferStatus" => $boletoResponse->transferStatus,
                    "paymentMethod" => $boletoResponse->paymentMethod
                ));
                $this->liquidoSalesOrderHelper->createOrUpdateLiquidoSalesOrder($orderData);
            }
        }

        $this->logger->info("[ {$className} Controller ]: Result data:", (array) $this->boletoResultData);
        $this->logger->info("###################### END ######################");

        $this->payInSession->setData("boletoResultData", $this->boletoResultData);

        return $this->resultPageFactory->create();
    }
}
