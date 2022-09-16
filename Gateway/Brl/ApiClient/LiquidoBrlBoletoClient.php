<?php

namespace Liquido\PayIn\Gateway\Brl\ApiClient;

use \Magento\Framework\HTTP\Client\Curl;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;

class LiquidoBrlBoletoClient
{

    private const BOLETO_ENDPOINT = "/v1/payments/charges/boleto";
    private const BOLETO_PDF_ENDPOINT = "/v1/payments/files/boleto/pdf/";

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

    public function createBoletoPayIn($data)
    {
        $url = $this->liquidoConfig->getVirgoBaseUrl() . $this::BOLETO_ENDPOINT;

        $className = static::class;
        $this->logger->info("[ {$className} ]: Url: {$url} - REQUEST payload:", $data);

        try {
            $jsonData = json_encode($data);
            $this->curl->post($url, $jsonData);
            $result = $this->curl->getBody();
            $boletoResponse = json_decode($result);

            $this->logger->info("[ {$className} ]: RESPONSE payload:", (array) $boletoResponse);

            return $boletoResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Boleto");
            $this->logger->error($e->getMessage());
        }
    }

    public function getBoletoPdfUrl($idempotencyKey)
    {
        $url = $this->liquidoConfig->getVirgoBaseUrl() . $this::BOLETO_PDF_ENDPOINT . $idempotencyKey;

        $className = static::class;

        try {
            $this->curl->get($url);
            $result = $this->curl->getBody();
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Boleto PDF file");
            $this->logger->error($e->getMessage());
        }
    }
}
