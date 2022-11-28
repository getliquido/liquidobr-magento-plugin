<?php

namespace Liquido\PayIn\Controller\LiquidoCO;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Model\LiquidoPayInSession;
use \Liquido\PayIn\Helper\LiquidoConfigData;

use \LiquidoBrl\PayInPhpSdk\Util\Config;
use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class PseForm implements ActionInterface
{
    private LoggerInterface $logger;
    private PageFactory $resultPageFactory;
    private DataObject $pseResultData;
    private PayInService $payInService;
    protected LiquidoPayInSession $payInSession;
    private LiquidoConfigData $liquidoConfig;

    public function __construct(
        PageFactory $resultPageFactory,
        PayInService $payInService,
        LiquidoPayInSession $payInSession,
        LoggerInterface $logger,
        LiquidoConfigData $liquidoConfig
    ) {
        $this->payInService = $payInService;
        $this->resultPageFactory = $resultPageFactory;
        $this->pseResultData = new DataObject(array());
        $this->payInSession = $payInSession;
        $this->logger = $logger;
        $this->liquidoConfig = $liquidoConfig;
    }

    public function execute()
    {
        $className = static::class;
        $this->logger->info("###################### BEGIN ######################");
        $this->logger->info("[ {$className} Controller ]: BOLETO Request received.");

        $this->pseResultData = new DataObject(array(
            'banks' => []
        ));

        $config = new Config(
            [
                'clientId' => $this->liquidoConfig->getClientId(),
                'clientSecret' => $this->liquidoConfig->getClientSecret(),
                'apiKey' => $this->liquidoConfig->getApiKey()
            ],
            $this->liquidoConfig->isProductionModeActived()
        );

        $pseResponse = $this->payInService->getPseFinancialInstitutions($config);

        $this->pseResultData->setData('banks', $pseResponse->data);

        $this->payInSession->setData('pseResultData', $this->pseResultData);
            
        return $this->resultPageFactory->create();
    }
}
