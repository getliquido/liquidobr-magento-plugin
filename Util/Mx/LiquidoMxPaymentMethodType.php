<?php

namespace Liquido\PayIn\Util\Mx;

use \LiquidoBrl\PayInPhpSdk\Util\Mexico\PaymentMethod;

abstract class LiquidoMxPaymentMethodType
{
    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            case PaymentMethod::BANK_TRANSFER:
                return LiquidoMxPayInMethod::BANK_TRANSFER["title"];
                break;
            default:
                return "";
        }
    }
}
