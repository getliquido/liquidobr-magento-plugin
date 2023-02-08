<?php

namespace Liquido\PayIn\Block\Co;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Co\LiquidoCoPaymentMethodType;

class LiquidoCoCash extends Template
{

    /**
     * @var LiquidoPayInSession
     */
    private LiquidoPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoPayInSession $payInSession
    )
    {
        $this->payInSession = $payInSession;
        parent::__construct($context);
    }

    public function getOrderId()
    {
        return $this->payInSession->getData("cashResultData")->getData("orderId");
    }

    public function getCashCode()
    {
        return $this->payInSession->getData("cashResultData")->getData("cashCode");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("cashResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("cashResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoCoPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function getSuccessMessage()
    {
        return $this->payInSession->getData("cashResultData")->getData("successMessage");
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("cashResultData")->getData("hasFailed");
    }

    public function getErrorMessage()
    {
        return $this->payInSession->getData("cashResultData")->getData("errorMessage");
    }
}