<?php

namespace Liquido\PayIn\Helper;

use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use \Magento\Framework\UrlInterface;

class LiquidoConfigData extends AbstractHelper
{

    private const LIQUIDO_SANDBOX_AUTH_URL = "https://auth-dev.liquido.com/oauth2/token";
    private const LIQUIDO_SANDBOX_VIRGO_BASE_URL = "https://api-qa.liquido.com";

    private const LIQUIDO_PRODUCTION_AUTH_URL = "https://authsg.liquido.com/oauth2/token";
    private const LIQUIDO_PRODUCTION_VIRGO_BASE_URL = "https://api.liquido.com";

    // public const CURRENCY = "BRL";
    // public const COUNTRY = "BR";
    public const PAYMENT_FLOW_DIRECT = "DIRECT";

    protected $objectManager;
    private $encryptor;

    public function __construct(
        Encryptor $encryptor
    )
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->encryptor = $encryptor;
    }

    public function getCallbackUrl()
    {
        $storeManager = $this->objectManager->get(
            '\Magento\Store\Model\StoreManagerInterface'
        );
        return $storeManager->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_WEB
        )  . "rest/V1/liquido-webhook";
    }

    /**
     * All data below are in "core_config_data" table in "path" column
     */
    public function isProductionModeActived()
    {
        try {
            $path = "payment/liquido/production_mode";
            $isProductionModeActived = $this->objectManager->get(
                'Magento\Framework\App\Config\ScopeConfigInterface'
            )->getValue($path);
            return $isProductionModeActived;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getAuthUrl()
    {
        try {
            if ($this->isProductionModeActived()) {
                return self::LIQUIDO_PRODUCTION_AUTH_URL;
            }
            return self::LIQUIDO_SANDBOX_AUTH_URL;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getVirgoBaseUrl()
    {
        try {
            if ($this->isProductionModeActived()) {
                return self::LIQUIDO_PRODUCTION_VIRGO_BASE_URL;
            }
            return self::LIQUIDO_SANDBOX_VIRGO_BASE_URL;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getClientId()
    {
        try {
            $path = "payment/liquido/sandbox_client_id";
            if ($this->isProductionModeActived()) {
                $path = "payment/liquido/prod_client_id";
            }
            $clientId = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue($path);
            return $clientId;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getClientSecret()
    {
        try {
            $path = "payment/liquido/sandbox_client_secret";
            if ($this->isProductionModeActived()) {
                $path = "payment/liquido/prod_client_secret";
            }
            $clientSecret = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue($path);
            $decryptedClientSecret = $this->encryptor->decrypt($clientSecret);
            return $decryptedClientSecret;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getApiKey()
    {
        try {
            $path = "payment/liquido/sandbox_api_key";
            if ($this->isProductionModeActived()) {
                $path = "payment/liquido/prod_api_key";
            }
            $apiKey = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue($path);
            return $apiKey;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    public function getCountry()
    {
        try {
            $path = "payment/liquido/country";
            $country = $this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue($path);
            return $country;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }
}
