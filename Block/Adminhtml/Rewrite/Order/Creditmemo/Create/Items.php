<?php

namespace Liquido\PayIn\Block\Adminhtml\Rewrite\Order\Creditmemo\Create;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{

    protected $_salesData;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        array $data = []
    ) {
        $this->_salesData = $salesData;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData);
    }

    protected function _prepareLayout()
    {
        /*$this->unsetChild("submit_button");

        $this->addChild(
            'submit_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Refund Offline Teste 2'),
                'class' => 'save submit-button primary',
                'onclick' => 'action-default action-warranty-order'
            ]
        );*/
    }

}