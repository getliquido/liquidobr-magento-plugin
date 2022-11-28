<?php

namespace Liquido\PayIn\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class LiquidoUpdateOrderObserver implements ObserverInterface
{

    public function __construct()
    {
    }

    public function execute(Observer $observer)
    {
        $incrementId = $observer->getData('incrementId');
        $newStatus = $observer->getData('newStatus');

        $this->updateOrderStatus($incrementId, $newStatus);
    }

    private function updateOrderStatus($incrementId, $newStatus)
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $collection = $objectManager->create('Magento\Sales\Model\Order');
            $order = $collection->loadByIncrementId($incrementId);
            if ($order->getStatus() != $newStatus) {
                $order->setStatus($newStatus);
                $order->save();
            }
        } catch (\Exception $e) {
            echo "Not found";
            echo $e;
        }
    }
}
