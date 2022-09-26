<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;

use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInMethod;

class LiquidoBrlBoletoForm extends Template
{
    public function getBoletoPayInMethodName()
    {
        return LiquidoBrlPayInMethod::BOLETO["title"];
    }
}
