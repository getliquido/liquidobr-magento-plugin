<?php

namespace Liquido\PayIn\Util\Common;

abstract class LiquidoPayInMethod
{
    public const CREDIT_CARD = [
        "title" => "Cartão de Crédito",
        "description" => "O pagamento poderá ser aprovado na hora.",
        "image" => "Liquido_PayIn::images/credit-card.png"
    ];
}
