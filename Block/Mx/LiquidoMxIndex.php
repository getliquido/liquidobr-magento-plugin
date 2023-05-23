<?php

namespace Liquido\PayIn\Block\Mx;

use \Magento\Framework\View\Element\Template;

use \Liquido\PayIn\Util\Mx\LiquidoMxPayInMethod;
use \Liquido\PayIn\Util\Common\LiquidoPayInMethod;
use \Liquido\PayIn\Util\Mx\LiquidoMxPayInViewRoute;

class LiquidoMxIndex extends Template
{

    public function getLiquidoMexicoPayInMethods()
    {
        $mexico_payin_methods = [
            LiquidoPayInMethod::CREDIT_CARD,
            LiquidoPayInMethod::CASH,
            LiquidoMxPayInMethod::BANK_TRANSFER
        ];
        return $mexico_payin_methods;
    }

    public function getPayInMethodViewRoute($_payin_method_title)
    {
        switch ($_payin_method_title) {
            case LiquidoPayInMethod::CASH["title"]:
                return LiquidoMxPayInViewRoute::CASH;
                break;
            case LiquidoMxPayInMethod::BANK_TRANSFER["title"]:
                return LiquidoMxPayInViewRoute::BANK_TRANSFER;
                break;
            case LiquidoPayInMethod::CREDIT_CARD["title"]:
                return LiquidoMxPayInViewRoute::CREDIT_CARD;
                break;
            default:
                return "#";
        }
    }
}
