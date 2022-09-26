<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Checkout\Model\Session;

use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInMethod;
use \Liquido\PayIn\Helper\Brl\LiquidoBrlOrderData;

class LiquidoBrlCreditCardForm extends Template
{

    protected Session $checkoutSession;
    private LiquidoBrlOrderData $liquidoOrderData;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        LiquidoBrlOrderData $liquidoOrderData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->liquidoOrderData = $liquidoOrderData;
    }

    public function getCardPayInMethodName()
    {
        return LiquidoBrlPayInMethod::CREDIT_CARD["title"];
    }

    private function getOrderTotal()
    {
        return $this->liquidoOrderData->getGrandTotal() / 100;
    }

    public function getCardInstallmentsTextsForOptions()
    {
        $textsForOptionsArray = array();

        $orderTotal = $this->getOrderTotal();
        if ($orderTotal >= 1) {
            for ($i = 1; $i <= 12; $i++) {

                $installmentAmount = $orderTotal / $i;
                $installmentAmountRound = round($installmentAmount, 2);
                if (floatval($installmentAmountRound) == 0.01) {
                    break;
                }

                $installmentValue = number_format($installmentAmountRound, 2, ',', '.');
                $optionInfo = $i . "x de R$ " . $installmentValue;
                array_push($textsForOptionsArray, $optionInfo);
            }
        } else {
            $orderTotal = number_format($orderTotal, 2, ',', '.');
            $optionInfo = "1 x de R$ {$orderTotal}";
            array_push($textsForOptionsArray, $optionInfo);
        }

        return $textsForOptionsArray;
    }
}
