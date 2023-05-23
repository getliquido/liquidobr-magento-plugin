<?php

namespace Liquido\PayIn\Util\Mx;

abstract class LiquidoMxPayInMethod
{
    public const BANK_TRANSFER = [
        "id" => "bank_transfer",
        "title" => "Transferencia bancaria",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/mx/bank-transfer.png",
        "image-selected"=> "Liquido_PayIn::images/mx/bank-transfer-selected.png",
    ];
}