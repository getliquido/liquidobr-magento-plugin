<?php

namespace Liquido\PayIn\Model;

use Magento\Framework\App\ObjectManager;

class MagentoSalesOrder
{
    public static function findOrder($incrementId)
    {
        try {
            $objectManager = ObjectManager::getInstance();
            $collection = $objectManager->create('Magento\Sales\Model\Order');
            $order = $collection->loadByIncrementId($incrementId);
            return $order;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function updateOrderStatus($order, $newStatus)
    {
        try {
            $order->setStatus($newStatus);
            $order->save();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
