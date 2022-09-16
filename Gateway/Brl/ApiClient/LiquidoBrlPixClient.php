<?php

namespace Liquido\PayIn\Gateway\Brl\ApiClient;

use \Magento\Framework\HTTP\Client\Curl;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;

class LiquidoBrlPixClient
{
    private const PIX_ENDPOINT = "/v1/payments/charges/pix";

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

    public function createPixPayIn($data)
    {
        $url = $this->liquidoConfig->getVirgoBaseUrl() . $this::PIX_ENDPOINT;

        $className = static::class;
        $this->logger->info("[ {$className} ]: Url: {$url} - REQUEST payload:", $data);

        try {
            $jsonData = json_encode($data);
            $this->curl->post($url, $jsonData);
            $result = $this->curl->getBody();
            $pixResponse = json_decode($result);

            $this->logger->info("[ {$className} ]: RESPONSE payload:", (array) $pixResponse);

            return $pixResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Pix Code");
            $this->logger->error($e->getMessage());
        }
    }
}
