<?php

namespace Liquido\PayIn\Util\Co;

abstract class LiquidoCoPayInMethod
{
    public const PSE = [
        "id" => "pse",
        "title" => "Transferencia bancaria",
        "description" => "El pago puede ser aprobado al instante.",
        "image" => "Liquido_PayIn::images/col/pse.png",
        "image-selected"=> "Liquido_PayIn::images/col/pse-selected.png",
    ];
}