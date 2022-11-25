<?php

namespace Liquido\PayIn\Util\Common;

use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod;

abstract class LiquidoPaymentMethodType
{
    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            case PaymentMethod::CREDIT_CARD:
                return LiquidoPayInMethod::CREDIT_CARD["title"];
                break;
            default:
                return "";
        }
    }
}
