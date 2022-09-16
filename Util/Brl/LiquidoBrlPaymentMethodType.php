<?php

namespace Liquido\PayIn\Util\Brl;

abstract class LiquidoBrlPaymentMethodType
{
    public const CREDIT_CARD = "CREDIT_CARD";
    public const PIX_STATIC_QR = "PIX_STATIC_QR";
    public const BOLETO = "BOLETO";

    public static function getPaymentMethodName($paymentMethodType)
    {
        switch ($paymentMethodType) {
            case self::CREDIT_CARD:
                return LiquidoBrlPayInMethod::CREDIT_CARD["title"];
                break;
            case self::PIX_STATIC_QR:
                return LiquidoBrlPayInMethod::PIX["title"];
                break;
            case self::BOLETO:
                return LiquidoBrlPayInMethod::BOLETO["title"];
                break;
            default:
                return "";
        }
    }
}
