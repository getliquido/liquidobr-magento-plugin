<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;

use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInMethod;
use \Liquido\PayIn\Util\Common\LiquidoPayInMethod;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInViewRoute;

class LiquidoBrlIndex extends Template
{

    public function getLiquidoBrazilPayInMethods()
    {
        $brazil_payin_methods = [
            LiquidoPayInMethod::CREDIT_CARD,
            LiquidoBrlPayInMethod::PIX,
            LiquidoBrlPayInMethod::BOLETO
        ];
        return $brazil_payin_methods;
    }

    public function getPayInMethodViewRoute($_payin_method_title)
    {
        switch ($_payin_method_title) {
            case LiquidoBrlPayInMethod::PIX["title"]:
                return LiquidoBrlPayInViewRoute::PIX;
                break;
            case LiquidoBrlPayInMethod::BOLETO["title"]:
                return LiquidoBrlPayInViewRoute::BOLETO;
                break;
            case LiquidoPayInMethod::CREDIT_CARD["title"]:
                return LiquidoBrlPayInViewRoute::CREDIT_CARD;
                break;
            default:
                return "#";
        }
    }
}
