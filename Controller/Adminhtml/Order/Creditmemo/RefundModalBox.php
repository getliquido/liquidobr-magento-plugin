<?php

namespace Liquido\PayIn\Controller\Adminhtml\Order\Creditmemo;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\NewAction;
use Magento\Backend\Model\Session;
use \Magento\Framework\DataObject;

use \Liquido\PayIn\Logger\Logger;
use \Liquido\PayIn\Helper\LiquidoConfigData;
use \Liquido\PayIn\Model\LiquidoPayInSession;

use \LiquidoBrl\PayInPhpSdk\Service\PayInService;

class RefundModalBox extends NewAction implements HttpPostActionInterface
{
    /**
     * Changes ACL Resource Id
     */
    //const ADMIN_RESOURCE = 'Magento_Sales::hold';
    /**
     * @inheritDoc
     */

    protected Logger $logger;
    private PayInService $payInService;
    private DataObject $banksListResultData;
    private LiquidoConfigData $liquidoConfigData;
    private LiquidoPayInSession $payInSession;
    private Session $backendSession;

    public function __construct(
        PayInService $payInService,
        LiquidoConfigData $liquidoConfigData,
        LiquidoPayInSession $payInSession,
        Logger $logger,
        Session $backendSession
    ) {
        $this->payInService = $payInService;
        $this->banksListResultData = new DataObject(array());
        $this->liquidoConfigData = $liquidoConfigData;
        $this->payInSession = $payInSession;
        $this->logger = $logger;
        $this->backendSession = $backendSession;
    }

    public function execute()
    {
        $this->logger->info("RefundModalBox:: EXECUTE");

        $this->banksListResultData = new DataObject(array(
            'banks' => null
        ));

        $banksListResponse = $this->payInService->getBanksList($this->liquidoConfigData->getCountry());
        $this->logger->info("Banks List", $banksListResponse);

        $this->banksListResultData->setData('banks', $banksListResponse->banks);
        $this->backendSession->setData('banksListResultData', $this->banksListResultData);

        return $this->resultRedirectFactory->create();
    }
}