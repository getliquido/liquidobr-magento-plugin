<?php

namespace Liquido\PayIn\Controller\Liquido;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\DataObject;

use \Liquido\PayIn\Helper\LiquidoOrderData;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Logger\Logger;
use \Liquido\PayIn\Model\LiquidoPayInSession;

use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Util\Country;
use \LiquidoBrl\PayInPhpSdk\Util\Currency;
use \LiquidoBrl\PayInPhpSdk\Model\PayInRequest;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class CreditCardForm implements ActionInterface
{
    private PageFactory $resultPageFactory;
    private DataObject $proposalResultData;
    private PayInService $payInService;
    private Logger $logger;
    private LiquidoConfigData $liquidoConfig;
    protected LiquidoPayInSession $payInSession;
    private LiquidoOrderData $liquidoOrderData;

    public function __construct(
        PageFactory $resultPageFactory,
        PayInService $payInService,
        Logger $logger,
        LiquidoPayInSession $payInSession,
        LiquidoConfigData $liquidoConfig,
        LiquidoOrderData $liquidoOrderData
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->proposalResultData = new DataObject(array());
        $this->payInService = $payInService;
        $this->logger = $logger;
        $this->payInSession = $payInSession;
        $this->liquidoConfig = $liquidoConfig;
        $this->liquidoOrderData = $liquidoOrderData;
    }

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: Credit Card Form Request received.");

        $this->proposalResultData = new DataObject(array(
            'id' => null,
            'proposalDetails' => [],
            'hasFailed' => false
        ));

        $config = new Config(
            [
                'clientId' => $this->liquidoConfig->getClientId(),
                'clientSecret' => $this->liquidoConfig->getClientSecret(),
                'apiKey' => $this->liquidoConfig->getApiKey()
            ],
            $this->liquidoConfig->isProductionModeActived()
        );

        try {
            $paymentPlanResponse = $this->payInService->getInstallmentPlans($config);
            $countryAndCurrency = $this->getCountryAndCurrency();

            $planRequest = new PayInRequest([
                "amount" => $this->liquidoOrderData->getGrandTotal(),    
                "currency" => $countryAndCurrency["currency"],
                "country" => $countryAndCurrency["country"],
                "installmentPlanId" => $paymentPlanResponse[0]->id
            ]);

            $proposalResponse = $this->payInService->createProposal($config, $planRequest);

            $this->proposalResultData->setData('id', $proposalResponse->id);
            $this->proposalResultData->setData('proposalDetails', $proposalResponse->proposalDetails->installmentProposalDetails);
        } catch (\Exception $e) {
            $this->proposalResultData->setData('hasFailed', true);
        }

        $this->payInSession->setData('proposalResultData', $this->proposalResultData);

        return $this->resultPageFactory->create();
    }

    public function getCountryAndCurrency()
    {
        switch ($this->liquidoConfig->getCountry()) {
            case Country::BRAZIL:
                return [
                    'country' => Country::BRAZIL,
                    'currency' => Currency::BRL
                ];
                break;
            case Country::COLOMBIA:
                return [
                    'country' => Country::COLOMBIA,
                    'currency' => Currency::COP
                ];
                break;
            case Country::MEXICO:
                return [
                    'country' => Country::MEXICO,
                    'currency' => Currency::MXN
                ];
                break;
        }
    }
}
