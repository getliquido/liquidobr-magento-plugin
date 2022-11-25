<?php

namespace Liquido\PayIn\Controller\LiquidoCO;

use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{
    private PageFactory $resultPageFactory;

    public function __construct(
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
