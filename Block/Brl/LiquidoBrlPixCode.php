<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\Brl\LiquidoBrlPayInSession;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlPixCode extends Template
{

    /**
     * @var LiquidoBrlPayInSession
     */
    private LiquidoBrlPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoBrlPayInSession $payInSession
    ) {
        $this->payInSession = $payInSession;
        parent::__construct($context);
    }

    public function getOrderId()
    {
        return $this->payInSession->getData("pixResultData")->getData("orderId");
    }

    public function getPixCode()
    {
        return $this->payInSession->getData("pixResultData")->getData("pixCode");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("pixResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("pixResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoBrlPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("pixResultData")->getData("hasFailed");
    }
}
