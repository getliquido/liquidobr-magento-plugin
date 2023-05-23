<?php

namespace Liquido\PayIn\Util\Co;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;

abstract class LiquidoCoPaymentMethodType
{
    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            case PaymentMethod::PSE:
                return LiquidoCoPayInMethod::PSE["title"];
                break;
            default:
                return "";
        }
    }
}
