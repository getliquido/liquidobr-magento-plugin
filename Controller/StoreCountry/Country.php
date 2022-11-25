<?php
namespace Liquido\PayIn\Controller\StoreCountry;
 
class Country extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
 
    protected $storeManager;
 
    protected $scopeConfig;
 
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
 
    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = [];
        try {
            $configValue = $this->scopeConfig->getValue(
                'payment/liquido/country',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $response = [
                'success' => true,
                'value' => __($configValue)
            ];
 
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'value' => __($e->getMessage())
            ];
            $this->messageManager->addError($e->getMessage());
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}