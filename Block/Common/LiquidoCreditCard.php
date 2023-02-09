<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Common\LiquidoPaymentMethodType;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;
use \Liquido\PayIn\Util\Co\LiquidoCoPaymentMethodType;

use \LiquidoBrl\PayInPhpSdk\Util\Common\PaymentMethod;
use \LiquidoBrl\PayInPhpSdk\Util\Country;

class LiquidoCreditCard extends Template
{

    /**
     * @var LiquidoPayInSession
     */
    private LiquidoPayInSession $payInSession;
    private LiquidoConfigData $liquidoConfig;

    public function __construct(
        Context $context,
        LiquidoPayInSession $payInSession,
        LiquidoConfigData $liquidoConfig
    )
    {
        $this->payInSession = $payInSession;
        parent::__construct($context);
        $this->liquidoConfig = $liquidoConfig;
    }

    public function getOrderId()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("orderId");
    }

    public function getOrderTotal()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("amount") / 100;
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        $paymentMethodName = LiquidoPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
        if ($paymentMethodName != null) {
            return $paymentMethodName;
        }

        $country = $this->payInSession->getData("creditCardResultData")->getData("country");
        switch ($country) {
            case Country::BRAZIL:
                return LiquidoBrlPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
                break;
            case Country::COLOMBIA:
                return LiquidoCoPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
                break;
            default:
                return null;
        }
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("hasFailed");
    }

    public function getInstallments()
    {
        if ($this->getPaymentMethodType() == PaymentMethod::CREDIT_CARD) {
            return $this->payInSession->getData("creditCardResultData")->getData("installments");
        }
        return 1;
    }

    public function getHowCustomerPaid()
    {
        $totalPaid = $this->getOrderTotal();
        $installments = $this->getInstallments();
        $howCustomerPaid = $installments . "x de R$ " . $totalPaid / $installments;
        return $howCustomerPaid;
    }

    public function getCountryRedirect()
    {
        $country = $this->liquidoConfig->getCountry();

        $link = '';
        if ($country == 'BR') {
            $link = '/checkout/liquidobrl/index';
        }

        if ($country == 'CO') {
            $link = '/checkout/liquidoco/index';
        }

        return $link;
    }

    public function getSuccessMessage()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("successMessage");
    }

    public function getErrorMessage()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("errorMessage");
    }

    public function getCardInfo()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("cardInfo");
    }
}