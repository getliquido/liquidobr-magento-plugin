<?php

namespace Liquido\PayIn\Util\Brl;

use \LiquidoBrl\PayInPhpSdk\Util\Brazil\PaymentMethod;

abstract class LiquidoBrlPaymentMethodType
{
    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            // case PaymentMethod::CREDIT_CARD:
            //     return LiquidoBrlPayInMethod::CREDIT_CARD["title"];
            //     break;
            case PaymentMethod::PIX_STATIC_QR:
                return LiquidoBrlPayInMethod::PIX["title"];
                break;
            case PaymentMethod::BOLETO:
                return LiquidoBrlPayInMethod::BOLETO["title"];
                break;
            default:
                return "";
        }
    }
}
