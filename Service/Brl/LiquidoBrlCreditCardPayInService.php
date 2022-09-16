<?php

namespace Liquido\PayIn\Service\Brl;

use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;
use Liquido\PayIn\Gateway\Brl\ApiClient\LiquidoBrlCreditCardClient;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlCreditCardPayInService
{

    private LiquidoBrlCreditCardClient $liquidoCreditCardPayInClient;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        LiquidoBrlConfigData $liquidoConfig,
        LiquidoBrlCreditCardClient $liquidoCreditCardPayInClient
    ) {
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoCreditCardPayInClient = $liquidoCreditCardPayInClient;
    }

    public function createCreditCardPayIn(
        $creditCardData
    ) {
        $className = static::class;
        $this->logger->info("[ {$className} ]: Credit Card data:", (array) $creditCardData);

        try {

            $callbackUrl = $this->liquidoConfig->getCallbackUrl();

            $data = [
                "idempotencyKey" => $creditCardData->getData("idempotencyKey"),
                "amount" => $creditCardData->getData("grandTotal"),
                "currency" => LiquidoBrlConfigData::CURRENCY,
                "country" => LiquidoBrlConfigData::COUNTRY,
                "paymentMethod" => LiquidoBrlPaymentMethodType::CREDIT_CARD,
                "paymentFlow" => LiquidoBrlConfigData::PAYMENT_FLOW_DIRECT,
                "payer" => [
                    "name" => $creditCardData->getData("customerName"),
                    "email" => $creditCardData->getData("customerEmail")
                ],
                "card" => [
                    "cardHolderName" => $creditCardData->getData("customerCardName"),
                    "cardNumber" => $creditCardData->getData("customerCardNumber"),
                    "expirationMonth" => $creditCardData->getData("customerCardExpireMonth"),
                    "expirationYear" => $creditCardData->getData("customerCardExpireYear"),
                    "cvc" => $creditCardData->getData("customerCardCVV")
                ],
                //  "riskData" => [
                //      "ipAddress" => "192.168.0.1"
                //  ],
                "description" => "Module Magento 2 Credit Card Request",
                "installments" => $creditCardData->getData("customerCardInstallments"),
                "callbackUrl" => $callbackUrl
            ];

            $creditCardResponse = $this->liquidoCreditCardPayInClient->createCreditCardPayIn($data);
            return $creditCardResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Credit Card");
            $this->logger->error($e->getMessage());
        }
    }
}
