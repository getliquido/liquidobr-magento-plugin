<?php

namespace Liquido\PayIn\Block\Common;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Util\Common\LiquidoPaymentMethodType;

class LiquidoCoCash extends Template
{

    /**
     * @var LiquidoPayInSession
     */
    private LiquidoPayInSession $payInSession;

    public function __construct(
        Context $context,
        LiquidoPayInSession $payInSession
    ) {
        $this->payInSession = $payInSession;
        parent::__construct($context);
    }

    public function getOrderId()
    {
        return $this->payInSession->getData("cashResultData")->getData("orderId");
    }

    public function getCashCode()
    {
        return $this->payInSession->getData("cashResultData")->getData("cashCode");
    }

    public function getTransferStatus()
    {
        return $this->payInSession->getData("cashResultData")->getData("transferStatus");
    }

    public function getPaymentMethodType()
    {
        return $this->payInSession->getData("cashResultData")->getData("paymentMethod");
    }

    public function getPaymentMethodName()
    {
        return LiquidoPaymentMethodType::getPaymentMethodName($this->getPaymentMethodType());
    }

    public function getInstructions()
    {
        if ($this->payInSession->getData("cashResultData")->getData("country") == 'CO') 
        {
            return [
                "establishments" => "Baloto, Banco de Bogotá, Bancolombia, Brinks, Davivienda, Efecty, Superpagos, Sured.",
                "efecty" => "* Para pagos en establecimientos Efecty presentar el número de convenio:",
                "convenio" => "112766",
                "country" => $this->payInSession->getData("cashResultData")->getData("country")
            ];
        } 
        elseif ($this->payInSession->getData("cashResultData")->getData("country") == 'MX') 
        {
            return [
                "establishments" => "BBVA:1420712, HSBC:7755, Santander:7292, BANORTE:3724, 7-ELEVEN:0, Soriana:0, Bodega Aurrera:198, Circulo K:0, Walmart y Walmart express:198, Sam's Club:198, Tiendas Extra:0, Kiosko:0, Farmacias YZA:0, Caja Cerano:0, Caja Morelia Valladolid:0, Caja Oblatos:0, SMB Rural:0, CALIMAX:0, FARMACIA LA MAS BARATA:0, FARMACIA S ROMA:0, SUPER DEL NORTE:0, Telecomm:3724, Via servicios:0",
                "efecty" => "",
                "convenio" => "",
                "country" => $this->payInSession->getData("cashResultData")->getData("country")
            ];
        }
    }

    public function getEStablishments()
    {
        $establishmentsInfo = $this->getInstructions();
        $establishments = explode(",", $establishmentsInfo['establishments']);
        $establishmentsList = [];
        foreach ($establishments as $establishment)
        {
            $establishmentsList[] = explode(":", $establishment);
        }
        return $establishmentsList;
    }

    public function getSuccessMessage()
    {
        return $this->payInSession->getData("cashResultData")->getData("successMessage");
    }

    public function hasFailed()
    {
        return $this->payInSession->getData("cashResultData")->getData("hasFailed");
    }

    public function getErrorMessage()
    {
        return $this->payInSession->getData("cashResultData")->getData("errorMessage");
    }
}