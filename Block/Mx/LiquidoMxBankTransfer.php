<?php

namespace Liquido\PayIn\Block\Mx;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Mx\LiquidoMxPaymentMethodType;

class LiquidoMxBankTransfer extends Template
{
    /**
     * @var LiquidoBrlPayInSession
     */
    private LiquidoPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoPayInSession $payInSession
    )
    {
        $this->payInSession = $payInSession;
        parent::__construct($context);
    }    


    public function getOrderId()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("orderId");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoMxPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function getSuccessMessage()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("successMessage");
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("hasFailed");
    }

    public function getErrorMessage()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("errorMessage");
    }

    public function getAmount()
    {
        $amount = $this->payInSession->getData("bankTransferResultData")->getData("amount") / 100;
        return number_format($amount, 0, ',', '.');
    }

    public function getBeneficiaryName()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("beneficiaryName");
    }

    public function getBankName()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("bankName");
    }

    public function getBankAccountType()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("bankAccountType");
    }

    public function getBankAccountNumber()
    {
        return $this->payInSession->getData("bankTransferResultData")->getData("bankAccountNumber");
    }
}