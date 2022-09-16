<?php

namespace Liquido\PayIn\Gateway\Brl\ApiClient;

use \Magento\Framework\HTTP\Client\Curl;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Helper\Brl\LiquidoBrlConfigData;

class LiquidoBrlAuthClient
{

    private const GRANT_TYPE = "client_credentials";

    protected Curl $curl;
    protected array $formData;
    private LoggerInterface $logger;
    protected LiquidoBrlConfigData $liquidoConfig;

    /** *** Dependency Injection is not working here */
    // public function __construct(
    //     Curl $_curl,
    //     LiquidoBrlConfigData $liquidoConfig
    // ) {
    //     $this->curl = $_curl;
    //     $this->curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
    //     $this->formData = [
    //         "client_id" => $liquidoConfig->getClientId(),
    //         "client_secret" => $liquidoConfig->getClientSecret(),
    //         "grant_type" => LiquidoBrlAuthClient::GRANT_TYPE,
    //     ];
    // }

    public function __construct()
    {
        $this->curl = new Curl;
        $this->curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
        $this->liquidoConfig = new LiquidoBrlConfigData;
        $this->formData = [
            "client_id" => $this->liquidoConfig->getClientId(),
            "client_secret" => $this->liquidoConfig->getClientSecret(),
            "grant_type" => LiquidoBrlAuthClient::GRANT_TYPE,
        ];
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function authenticate()
    {
        $url = $this->liquidoConfig->getAuthUrl();

        $className = static::class;
        $this->logger->info("[ {$className} ]: Url: {$url} - REQUEST payload:", $this->formData);

        try {
            $this->curl->post($url, $this->formData);
            $result = $this->curl->getBody();
            $authResponse = json_decode($result);

            $this->logger->info("[ {$className} ]: RESPONSE payload:", (array) $authResponse);

            return $authResponse;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while request Liquido Access Token");
            $this->logger->error($e->getMessage());
            return null;
        }
    }
}
