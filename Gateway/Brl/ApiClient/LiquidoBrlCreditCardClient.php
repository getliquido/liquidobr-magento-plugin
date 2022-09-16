<?php

namespace Liquido\PayIn\Gateway\Brl\ApiClient;

use \Magento\Framework\HTTP\Client\Curl;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;

class LiquidoBrlCreditCardClient
{

    private const CREDIT_CARD_ENDPOINT = "/v1/payments/charges/card";

    protected Curl $curl;
    private LoggerInterface $logger;
    protected LiquidoBrlConfigData $liquidoConfig;

    public function __construct(
        Curl $_curl,
        LoggerInterface $logger,
        LiquidoBrlConfigData $liquidoConfig,
        LiquidoBrlAuthClient $liquidoAuthClient
    ) {
        $this->curl = $_curl;
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("x-api-key", $this->liquidoConfig->getApiKey());

        $liquidoAuthClient->setLogger($logger);
        $authResponse = $liquidoAuthClient->authenticate();
        if ($authResponse != null) {
            $this->curl->addHeader("Authorization", "Bearer $authResponse->access_token");
        }
    }

    public function createCreditCardPayIn($data)
    {
        $url = $this->liquidoConfig->getVirgoBaseUrl() . $this::CREDIT_CARD_ENDPOINT;

        $className = static::class;
        $this->logger->info("[ {$className} ]: Url: {$url} - REQUEST payload:", $data);

        try {
            $jsonData = json_encode($data);
            $this->curl->post($url, stripslashes($jsonData));
            $result = $this->curl->getBody();
            $creditCardResponse = json_decode($result);

            $this->logger->info("[ {$className} ]: RESPONSE payload:", (array) $creditCardResponse);

            return $creditCardResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Credit Card");
            $this->logger->error($e->getMessage());
        }
    }
}
