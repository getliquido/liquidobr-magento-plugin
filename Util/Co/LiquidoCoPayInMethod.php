<?php

namespace Liquido\PayIn\Util\Co;

abstract class LiquidoCoPayInMethod
{
    public const PSE = [
        "id" => "pse",
        "title" => "Transferencia bancaria",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/col/pse.png"
    ];
    public const CASH = [
        "id" => "cash",
        "title" => "Pago en efectivo",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/col/efecty.png"
    ];
// public const CREDIT_CARD = [
//     "title" => "Tarjeta de crédito",
//     "description" => "El pago puede ser aprobado al instante.",
//     "image" => "Liquido_PayIn::images/credit-card.png"
// ];
}