<?php

namespace Liquido\PayIn\Model;

use Exception;
use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use \Magento\Sales\Model\Order;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\App\ObjectManager;

/**
 * Class Delete
 * @package HelloMage\DeleteCreditmemo\Model\Creditmemo
 */
class LiquidoDeleteCreditmemo
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var Order
     */
    protected $order;

    private LoggerInterface $logger;
    private ObjectManager $objectManager;

    /**
     * Delete constructor.
     * @param Order $order
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        Order $order,
        CreditmemoRepositoryInterface $creditmemoRepository,
        LoggerInterface $logger
    ) {
        $this->order = $order;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @param $creditmemoId
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    public function deleteCreditmemo($creditmemoId)
    {
        $this->logger->info("###################### DELETE CREDIT MEMO ######################");
        
        $creditmemo = $this->creditmemoRepository->get($creditmemoId);
        $creditmemoItems = $creditmemo->getItems();

        $orderId = $creditmemo->getOrderId();
        $order = $this->order->load($orderId);
        $orderItems = $order->getItems();

        foreach ($orderItems as $item) 
        {
            foreach ($creditmemoItems as $creditmemoItem) 
            {
                if ($creditmemoItem->getOrderItemId() == $item->getItemId()) 
                {
                    $item->setQtyRefunded($item->getQtyRefunded() - $creditmemoItem->getQty());
                    $item->setTaxRefunded($item->getTaxRefunded() - $creditmemoItem->getTaxAmount());
                    $item->setBaseTaxRefunded($item->getBaseTaxRefunded() - $creditmemoItem->getBaseTaxAmount());

                    $discountTaxItem = $item->getDiscountTaxCompensationRefunded();
                    $discountTaxCredit = $creditmemoItem->getDiscountTaxCompensationAmount();
                    $item->setDiscountTaxCompensationRefunded($discountTaxItem - $discountTaxCredit);

                    $baseDiscountItem = $item->getBaseDiscountTaxCompensationRefunded();
                    $baseDiscountCredit = $creditmemoItem->getBaseDiscountTaxCompensationAmount();
                    $item->setBaseDiscountTaxCompensationRefunded($baseDiscountItem - $baseDiscountCredit);

                    $item->setAmountRefunded($item->getAmountRefunded() - $creditmemoItem->getRowTotal());
                    $item->setBaseAmountRefunded($item->getBaseAmountRefunded() - $creditmemoItem->getBaseRowTotal());
                    $item->setDiscountRefunded($item->getDiscountRefunded() - $creditmemoItem->getDiscountAmount());
                    $item->setBaseDiscountRefunded($item->getBaseDiscountRefunded() - $creditmemoItem->getBaseDiscountAmount());
                }
            }
        }

        $order->setBaseTotalRefunded($order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal());
        $order->setTotalRefunded($order->getTotalRefunded() - $creditmemo->getGrandTotal());
        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() - $creditmemo->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded() - $creditmemo->getSubtotal());
        $order->setBaseTaxRefunded($order->getBaseTaxRefunded() - $creditmemo->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded() - $creditmemo->getTaxAmount());
        $order->setBaseDiscountTaxCompensationRefunded($order->getBaseDiscountTaxCompensationRefunded() - $creditmemo->getBaseDiscountTaxCompensationAmount()
        );
        $order->setDiscountTaxCompensationRefunded($order->getDiscountTaxCompensationRefunded() - $creditmemo->getDiscountTaxCompensationAmount());
        $order->setBaseShippingRefunded($order->getBaseShippingRefunded() - $creditmemo->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded() - $creditmemo->getShippingAmount());
        $order->setBaseShippingTaxRefunded($order->getBaseShippingTaxRefunded() - $creditmemo->getBaseShippingTaxAmount());
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded() - $creditmemo->getShippingTaxAmount());
        $order->setAdjustmentPositive($order->getAdjustmentPositive() - $creditmemo->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive($order->getBaseAdjustmentPositive() - $creditmemo->getBaseAdjustmentPositive());
        $order->setAdjustmentNegative($order->getAdjustmentNegative() - $creditmemo->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative($order->getBaseAdjustmentNegative() - $creditmemo->getBaseAdjustmentNegative());
        $order->setDiscountRefunded($order->getDiscountRefunded() - $creditmemo->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() - $creditmemo->getBaseDiscountAmount());

        // if credit memo refund done in offline mode
        $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() - $creditmemo->getGrandTotal());
        $order->setBaseTotalOfflineRefunded($order->getBaseTotalOfflineRefunded() - $creditmemo->getBaseGrandTotal());
    
        $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)
        ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_COMPLETE))
        ->save();

        $creditmemo = $order->getCreditmemosCollection()->getItemById($creditmemoId);
        $creditmemo->delete();
    }
}
