<?php

namespace Liquido\PayIn\Block\Co;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Co\LiquidoCoPaymentMethodType;

class LiquidoCoPse extends Template
{

    /**
     * @var LiquidoBrlPayInSession
     */
    private LiquidoPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoPayInSession $payInSession
    ) {
        $this->payInSession = $payInSession;
        parent::__construct($context);
    }

    public function getOrderId()
    {
        return $this->payInSession->getData("pseResultData")->getData("orderId");
    }

    public function getOrderTotal()
    {
        return $this->payInSession->getData("pseResultData")->getData("amount") / 100;
    }

    public function getPseLink()
    {
        return $this->payInSession->getData("pseResultData")->getData("pseLink");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("pseResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("pseResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoCoPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("pseResultData")->getData("hasFailed");
    }
}
