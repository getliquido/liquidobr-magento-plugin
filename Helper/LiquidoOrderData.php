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
    ) {
        $this->customerSession = $customerSession;
        $this->mathRandom = $mathRandom;
        $this->logger = $logger;
        $this->orderData = $customerSession->getLastRealOrder();
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
}
