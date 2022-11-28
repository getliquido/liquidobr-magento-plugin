<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlBoleto extends Template
{
    /**
     * @var LiquidoPayInSession
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
        return $this->payInSession->getData("boletoResultData")->getData("orderId");
    }

    public function getBoletoDigitalLine()
    {
        return $this->payInSession->getData("boletoResultData")->getData("boletoDigitalLine");
    }

    public function getBoletoUrl()
    {
        return $this->payInSession->getData("boletoResultData")->getData("boletoUrl");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("boletoResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("boletoResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoBrlPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("boletoResultData")->getData("hasFailed");
    }
}
