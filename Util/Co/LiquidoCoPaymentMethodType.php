<?php

namespace Liquido\PayIn\Util\Co;

use \LiquidoBrl\PayInPhpSdk\Util\Colombia\PaymentMethod;

abstract class LiquidoCoPaymentMethodType
{
    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            // case PaymentMethod::CREDIT_CARD:
            //     return LiquidoCoPayInMethod::CREDIT_CARD["title"];
            //     break;
            case PaymentMethod::PSE:
                return LiquidoCoPayInMethod::PSE["title"];
                break;
            case PaymentMethod::CASH:
                return LiquidoCoPayInMethod::CASH["title"];
                break;
            default:
                return "";
        }
    }
}
