<?php

namespace Liquido\PayIn\Service\Brl;

use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;
use Liquido\PayIn\Gateway\Brl\ApiClient\LiquidoBrlPixClient;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlPixPayInService
{

    private LoggerInterface $logger;
    private LiquidoBrlConfigData $liquidoConfig;
    private LiquidoBrlPixClient $liquidoPixPayInClient;

    public function __construct(
        LoggerInterface $logger,
        LiquidoBrlConfigData $liquidoConfig,
        LiquidoBrlPixClient $liquidoPixPayInClient
    ) {
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoPixPayInClient = $liquidoPixPayInClient;
    }

    public function createLiquidoPixPayIn($pixData)
    {
        $className = static::class;
        $this->logger->info("[ {$className} ]: Pix data:", (array) $pixData);

        try {

            $callbackUrl = $this->liquidoConfig->getCallbackUrl();

            $data = [
                "idempotencyKey" => $pixData->getData("idempotencyKey"),
                "amount" => $pixData->getData("grandTotal"),
                "currency" => LiquidoBrlConfigData::CURRENCY,
                "country" => LiquidoBrlConfigData::COUNTRY,
                "paymentMethod" => LiquidoBrlPaymentMethodType::PIX_STATIC_QR,
                "paymentFlow" => LiquidoBrlConfigData::PAYMENT_FLOW_DIRECT,
                "callbackUrl" => $callbackUrl,
                "payer" => [
                    "email" => $pixData->getData("customerEmail")
                ],
                "description" => "Module Magento 2 PIX Request"
            ];

            $pixResponse = $this->liquidoPixPayInClient->createPixPayIn($data);
            return $pixResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Pix Code");
            $this->logger->error($e->getMessage());
        }
    }
}
