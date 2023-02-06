<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Common\LiquidoPaymentMethodType;

use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod;

class LiquidoOrderSummary extends Template
{

    // private LiquidoPayInSession $payInSession;
    // private LiquidoConfigData $liquidoConfig;

    public function __construct(
        // Context $context,
        // LiquidoPayInSession $payInSession,
        // LiquidoConfigData $liquidoConfig
    )
    {
        // $this->payInSession = $payInSession;
        // parent::__construct($context);
        // $this->liquidoConfig = $liquidoConfig;
    }

    public function getGrandTotal()
    {
        return 10500;
    }

}