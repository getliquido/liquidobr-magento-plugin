<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Checkout\Model\Session;

use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Util\Common\LiquidoPayInMethod;
use \Liquido\PayIn\Helper\LiquidoOrderData;

class LiquidoCreditCardForm extends Template
{

    protected Session $checkoutSession;
    private LiquidoOrderData $liquidoOrderData;
    private LiquidoConfigData $liquidoConfig;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        LiquidoOrderData $liquidoOrderData,
        LiquidoConfigData $liquidoConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->liquidoConfig = $liquidoConfig;
    }

    public function getCardPayInMethodName()
    {
        return LiquidoPayInMethod::CREDIT_CARD["title"];
    }

    private function getOrderTotal()
    {
        return $this->liquidoOrderData->getGrandTotal() / 100;
    }

    public function getCardInstallmentsTextsForOptions()
    {
        $textsForOptionsArray = array();
        $symbol = ($this->getCountry() == 'BR') ? 'R$' : '$';
        $orderTotal = $this->getOrderTotal();
        if ($orderTotal >= 1) {
            for ($i = 1; $i <= 12; $i++) {

                $installmentAmount = $orderTotal / $i;
                $installmentAmountRound = round($installmentAmount, 2);
                if (floatval($installmentAmountRound) == 0.01) {
                    break;
                }

                $installmentValue = number_format($installmentAmountRound, 2, ',', '.');
                $optionInfo = $i . "x de " . $symbol . $installmentValue;
                array_push($textsForOptionsArray, $optionInfo);
            }
        } else {
            $orderTotal = number_format($orderTotal, 2, ',', '.');
            $optionInfo = "1 x de {$symbol} {$orderTotal}";
            array_push($textsForOptionsArray, $optionInfo);
        }

        return $textsForOptionsArray;
    }

    public function getCountry()
    {
        return $this->liquidoConfig->getCountry();
    }
}
