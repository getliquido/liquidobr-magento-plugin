<?php

namespace Liquido\PayIn\Block\Adminhtml\Order\Creditmemo\Create;

use \Magento\Backend\Model\Session;

use \Liquido\PayIn\Logger\Logger;
use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Helper\LiquidoConfigData;

use \LiquidoBrl\PayInPhpSdk\Service\PayInService;
use \LiquidoBrl\PayInPhpSdk\Util\Country;

class RefundModalBox extends \Magento\Backend\Block\Template
{
    protected Logger $logger;
    private LiquidoPayInSession $payInSession;
    protected Session $backendSession;
    private LiquidoConfigData $liquidoConfigData;
    private PayInService $payInService;

    public function __construct(
        Logger $logger,
        LiquidoPayInSession $payInSession,
        Session $backendSession,
        LiquidoConfigData $liquidoConfigData,
        PayInService $payInService,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->backendSession = $backendSession;
        $this->liquidoConfigData = $liquidoConfigData;
        $this->payInService = $payInService;
        parent::__construct($context, $data);
    }

    public function getFormUrl()
    {
        return $this->getUrl('sales/*/save', ['_current' => true]);
    }

    public function getBanksList()
    {
        $banksListResponse = $this->payInService->getBanksList($this->liquidoConfigData->getCountry());
        $banks = $banksListResponse['banks'];
        $this->logger->info("Banks List", $banks);
        return $banks;
    }

    public function getDocumentType()
    {
        if($this->liquidoConfigData->getCountry() == Country::COLOMBIA || $this->liquidoConfigData->getCountry() == Country::MEXICO)
        {
            return ["CC", "CE", "NIT"];
        }
        else
        {
            return ["CPF", "CNPJ"];
        }
    }

}