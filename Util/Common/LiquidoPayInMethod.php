<?php

namespace Liquido\PayIn\Util\Common;

abstract class LiquidoPayInMethod
{
    public const CREDIT_CARD = [
        "id" => "credit-card",
        "title" => "Cartão de crédito",
        "description" => "O pagamento poderá ser aprovado na hora.",
        "image" => "Liquido_PayIn::images/common/credit-card.png",
        "image-selected"=> "Liquido_PayIn::images/common/credit-card-selected.png",
    ];
}
