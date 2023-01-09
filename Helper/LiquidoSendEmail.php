<?php

namespace Liquido\PayIn\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;

class LiquidoSendEmail extends AbstractHelper
{
    protected $transportBuilder;
    protected $storeManager;
    protected $inlineTranslation;
    protected $logo;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        Logo $logo
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->logo = $logo;
        parent::__construct($context);
    }

    public function sendEmail($customerEmail, $customerName, $cashCode = null)
    {
        $templateId = 'cash_email';
        $fromEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $fromName = $this->scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $toEmail = $customerEmail;

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $templateVars = [
                'customerName' => $customerName,
                'logo' => $this->logo->getLogoSrc(),
                'cashCode' => $cashCode
            ];

            $from = [
                'email' => $fromEmail,
                'name' => $fromName
            ];

            $this->inlineTranslation->suspend();

            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
