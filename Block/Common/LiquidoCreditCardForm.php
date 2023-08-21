<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Checkout\Model\Session;

use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Util\Common\LiquidoPayInMethod;
use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Model\LiquidoPayInSession;

use \LiquidoBrl\PayInPhpSdk\Util\Country;

class LiquidoCreditCardForm extends Template
{

    protected Session $checkoutSession;
    private LiquidoOrderData $liquidoOrderData;
    private LiquidoConfigData $liquidoConfig;
    private LiquidoPayInSession $payInSession;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        LiquidoOrderData $liquidoOrderData,
        LiquidoConfigData $liquidoConfig,
        LiquidoPayInSession $payInSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->liquidoOrderData = $liquidoOrderData;
        $this->liquidoConfig = $liquidoConfig;
        $this->payInSession = $payInSession;
    }

    public function getCardPayInMethodName()
    {
        return LiquidoPayInMethod::CREDIT_CARD["title"];
    }

    private function getOrderTotal()
    {
        return $this->liquidoOrderData->getGrandTotal() / 100;
    }

    /*public function getCardInstallmentsTextsForOptions()
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
    }*/

    public function getCountry()
    {
        return $this->liquidoConfig->getCountry();
    }

    public function getProposalId()
    {
        return $this->payInSession->getData('proposalResultData')->getData('id');
    }

    public function getProposalDetails()
    {
        return $this->payInSession->getData('proposalResultData')->getData('proposalDetails');
    }

    public function getCardInstallmentsTextsForOptions()
    {
        $textsForOptionsArray = [];
        $orderTotal = $this->getOrderTotal();
        $detailsByCountry = $this->getDetailsByCountry();

        if ($orderTotal >= 1) {
            if($this->payInSession->getData('proposalResultData')->getData('hasFailed') || ($this->getCountry() == Country::MEXICO || $this->getCountry() == Country::COLOMBIA))
            {
                for ($i = $detailsByCountry['init']; $i <= $detailsByCountry['maxInstallments']; $i += $detailsByCountry['increment']) {

                    $installmentAmount = $orderTotal / $i;
                    $installmentAmountRound = round($installmentAmount, 2);
                    if (floatval($installmentAmountRound) == 0.01) {
                        break;
                    }
    
                    $installmentValue = number_format($installmentAmountRound, 2, ',', '.');
                    $optionInfo = $i . "x de " . $detailsByCountry['currencySymbol'] . $installmentValue;
                    array_push($textsForOptionsArray, $optionInfo);

                    if($this->getCountry() == Country::MEXICO && $i == 12)
                    {
                        $detailsByCountry['increment'] = 6;
                    }
                }
            }
            else
            {
                $proposalDetails = $this->getProposalDetails();
                foreach($proposalDetails as $optionDetail){
                    $installmentAmount = $optionDetail->installmentAmount / 100;
                    $installmentValue = number_format($installmentAmount, 2, ',', '.');
                    $optionInfo = $optionDetail->installments . "x de " . $detailsByCountry['currencySymbol'] . $installmentValue;
                    array_push($textsForOptionsArray, $optionInfo);
                }
            }
        } else {
            $orderTotal = number_format($orderTotal, 2, ',', '.');
            $optionInfo = "1 x de {$detailsByCountry['currencySymbol']} {$orderTotal}";
            array_push($textsForOptionsArray, $optionInfo);
        }
        
        return $textsForOptionsArray;
    }

    public function getDetailsByCountry()
    {
        switch ($this->getCountry()) {
            case Country::BRAZIL:
                return [
                    'currencySymbol' => 'R$',
                    'maxInstallments' => 12,
                    'increment' => 1,
                    'init' => 1
                ];
                break;
            case Country::MEXICO:
                return [
                    'currencySymbol' => '$',
                    'maxInstallments' => 18,
                    'increment' => 3,
                    'init' => 3
                ];
                break;            
            case Country::COLOMBIA:
                return [
                        'currencySymbol' => '$',
                        'maxInstallments' => 12,
                        'increment' => 1,
                        'init' => 1
                    ];
                break;
        }
    }
}
