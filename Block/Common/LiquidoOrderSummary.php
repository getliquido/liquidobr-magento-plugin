<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Helper\LiquidoOrderData;

class LiquidoOrderSummary extends Template
{

    private LiquidoConfigData $liquidoConfig;
    private LiquidoOrderData $liquidoOrderData;

    public function __construct(
        Context $context,
        LiquidoConfigData $liquidoConfig,
        LiquidoOrderData $liquidoOrderData
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