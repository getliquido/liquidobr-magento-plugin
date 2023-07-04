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
                "establishments" => "* Establecimientos aliados: Baloto, Banco de Bogotá, Bancolombia, Brinks, Davivienda, Efecty, Superpagos, Sured.",
                "efecty" => "* Para pagos en establecimientos Efecty presentar el número de convenio:",
                "convenio" => "112766"
            ];
        } 
        elseif ($this->payInSession->getData("cashResultData")->getData("country") == 'MX') 
        {
            return [
                "establishments" => "* Establecimientos aliados: BBVA, HSBC, Santander, BANORTE, 7-ELEVEN, Soriana, Bodega Aurrera, Circulo K, 
                    Walmart y Walmart express, Sam's Club, Tiendas Extra, Kiosko, Farmacias YZA, Caja Cerano, Caja Morelia Valladolid, Caja Oblatos, 
                    SMB Rural, CALIMAX, FARMACIA LA MAS BARATA, FARMACIAS ROMA, SUPER DEL NORTE, Telecomm, Via servicios",
                "efecty" => "* Para pagos en establecimientos Efecty presentar el número de convenio:",
                "convenio" => "112766"
            ];
        }
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