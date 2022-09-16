<?php

namespace Liquido\PayIn\Service\Brl;

use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;
use Liquido\PayIn\Gateway\Brl\ApiClient\LiquidoBrlBoletoClient;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlBoletoPayInService
{

    private LiquidoBrlBoletoClient $liquidoBoletoClient;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        LiquidoBrlConfigData $liquidoConfig,
        LiquidoBrlBoletoClient $liquidoBoletoClient
    ) {
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoBoletoClient = $liquidoBoletoClient;
    }

    public function createLiquidoBoletoPayIn(
        $boletoData
    ) {
        $className = static::class;
        $this->logger->info("[ {$className} ]: Boleto data:", (array) $boletoData);

        try {

            $callbackUrl = $this->liquidoConfig->getCallbackUrl();

            $data = [
                "idempotencyKey" => $boletoData->getData("idempotencyKey"),
                "amount" => $boletoData->getData("grandTotal"),
                "currency" => LiquidoBrlConfigData::CURRENCY,
                "country" => LiquidoBrlConfigData::COUNTRY,
                "paymentMethod" => LiquidoBrlPaymentMethodType::BOLETO,
                "paymentFlow" => LiquidoBrlConfigData::PAYMENT_FLOW_DIRECT,
                "payer" => [
                    "name" => $boletoData->getData("customerName"),
                    "document" => [
                        "documentId" => $boletoData->getData("customerCpf"),
                        "type" => "CPF"
                    ],
                    "billingAddress" => [
                        "zipCode" => $boletoData->getData("customerBillingAddress")->getPostcode(),
                        "state" => $boletoData->getData("customerBillingAddress")->getRegionCode(),
                        "city" => $boletoData->getData("customerBillingAddress")->getCity(),
                        "district" => "Unknown",
                        "street" => $boletoData->getData("streetText"),
                        "number" => "Unknown",
                        "country" => $boletoData->getData("customerBillingAddress")->getCountryId()
                    ],
                    "email" => $boletoData->getData("customerBillingAddress")->getEmail()
                ],
                "paymentTerm" => [
                    "paymentDeadline" => $boletoData->getData("paymentDeadline")
                ],
                "callbackUrl" => $callbackUrl,
                "description" => "Module Magento 2 Boleto Request"
            ];

            $boletoResponse = $this->liquidoBoletoClient->createBoletoPayIn($data);
            return $boletoResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Boleto");
            $this->logger->error($e->getMessage());
        }
    }

    public function getLiquidoBoletoPdfUrl(
        $idempotencyKey
    ) {
        try {
            $boletoPdfJsonResponse = $this->liquidoBoletoClient->getBoletoPdfUrl(
                $idempotencyKey
            );
            return $boletoPdfJsonResponse;
        } catch (\Exception $e) {
            echo $e;
        }
    }
}
