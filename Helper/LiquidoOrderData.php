<?php

namespace Liquido\PayIn\Helper;

use \Magento\Checkout\Model\Session;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\Math\Random;
use \Psr\Log\LoggerInterface;

class LiquidoOrderData extends AbstractHelper
{

    private $orderData;
    private Session $customerSession;
    private Random $mathRandom;
    private LoggerInterface $logger;

    public function __construct(
        Session $customerSession,
        Random $mathRandom,
        LoggerInterface $logger
    )
    {
        $this->customerSession = $customerSession;
        $this->mathRandom = $mathRandom;
        $this->logger = $logger;
        $this->orderData = $customerSession->getLastRealOrder();

        // echo '<pre>';
        // print_r($this->orderData->getData());
        // echo '</pre>';

    }

    public function getCustomerName()
    {
        $className = static::class;
        try {
            return $this->orderData->getCustomerName();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting customer name");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getCustomerEmail()
    {
        $className = static::class;
        try {
            return $this->orderData->getCustomerEmail();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting customer email");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getIncrementId()
    {
        $className = static::class;
        try {
            return $this->orderData->getIncrementId();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting increment id");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getGrandTotal()
    {
        $className = static::class;
        try {
            return $this->orderData->getGrandTotal() * 100;
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting grand total");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getBillingAddress()
    {
        $className = static::class;
        try {
            return $this->orderData->getBillingAddress();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting billing address");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function setPixCode($pixCode)
    {
        // ->unsCustomName();
        $this->customerSession->setPixCode($pixCode);
    }

    // Generate unique token
    public function generateUniqueToken()
    {
        return $this->mathRandom->getUniqueHash();
    }

    public function getSubtotal()
    {
        $className = static::class;
        try {
            return $this->orderData->getSubTotal();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting sub total");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getDiscountAmount()
    {
        $className = static::class;
        try {
            return $this->orderData->getDiscountAmount();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting discounts");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getShippingAmount()
    {
        $className = static::class;
        try {
            return $this->orderData->getShippingAmount();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting shipping amount");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getOriginalGrandTotal()
    {
        $className = static::class;
        try {
            return $this->orderData->getGrandTotal();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting grand total");
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getOrderCurrencyCode()
    {
        $className = static::class;
        try {
            return $this->orderData->getOrderCurrencyCode();
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while getting order currency code");
            $this->logger->error($e->getMessage());
            return null;
        }
    }
}