<?php

namespace Liquido\PayIn\Block\Brl;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\Brl\LiquidoBrlPayInSession;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPaymentMethodType;

class LiquidoBrlCreditCard extends Template
{

    /**
     * @var LiquidoBrlPayInSession
     */
    private LiquidoBrlPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoBrlPayInSession $payInSession
    ) {
        $this->payInSession = $payInSession;
        parent::__construct($context);
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
        return LiquidoBrlPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("creditCardResultData")->getData("hasFailed");
    }

    public function getInstallments()
    {
        if ($this->getPaymentMethodType() == LiquidoBrlPaymentMethodType::CREDIT_CARD) {
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
}
