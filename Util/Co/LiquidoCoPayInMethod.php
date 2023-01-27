<?php

namespace Liquido\PayIn\Util\Co;

abstract class LiquidoCoPayInMethod
{
    public const PSE = [
        "title" => "PSE",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/pse.png"
    ];
    public const CASH = [
        "title" => "Pago en efectivo",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/efecty.png"
    ];
    // public const CREDIT_CARD = [
    //     "title" => "Tarjeta de crédito",
    //     "description" => "El pago puede ser aprobado al instante.",
    //     "image" => "Liquido_PayIn::images/credit-card.png"
    // ];
}