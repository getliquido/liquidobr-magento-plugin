<?php

namespace Liquido\PayIn\Util\Brl;

abstract class LiquidoBrlPayInMethod
{
    public const PIX = [
        "title" => "Pix",
        "description" => "O pagamento será aprovado na hora.",
        "image" => "Liquido_PayIn::images/brl/pix.png"
    ];
    public const BOLETO = [
        "title" => "Boleto",
        "description" => "O pagamento será aprovado em até 3 dias úteis.",
        "image" => "Liquido_PayIn::images/brl/boleto.png"
    ];
}
