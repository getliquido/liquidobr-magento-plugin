<?php

namespace Liquido\PayIn\Helper\Brl;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Model\Brl\LiquidoBrlSalesOrder;
use \Liquido\PayIn\Model\Brl\ResourceModel\LiquidoBrlSalesOrder as LiquidoBrlSalesOrderResourceModel;
use \Liquido\PayIn\Model\Brl\ResourceModel\LiquidoBrlSalesOrder\Collection as LiquidoBrlSalesOrderCollection;
use \Liquido\PayIn\Model\MagentoSalesOrder;
use \Liquido\PayIn\Util\Brl\LiquidoBrlPayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;

class LiquidoBrlSalesOrderHelper extends AbstractHelper
{

    private LoggerInterface $logger;
    private TimezoneInterface $timezoneInterface;
    private LiquidoBrlSalesOrder $liquidoBrlSalesOrder;
    private LiquidoBrlSalesOrderResourceModel $liquidoBrlSalesOrderResourceModel;
    private LiquidoBrlSalesOrderCollection $liquidoBrlSalesOrderCollection;
    private LiquidoBrlConfigData $liquidoBrlConfigData;

    public function __construct(
        LoggerInterface $logger,
        TimezoneInterface $timezoneInterface,
        LiquidoBrlSalesOrder $liquidoBrlSalesOrder,
        LiquidoBrlSalesOrderResourceModel $liquidoBrlSalesOrderResourceModel,
        LiquidoBrlSalesOrderCollection $liquidoBrlSalesOrderCollection,
        LiquidoBrlConfigData $liquidoBrlConfigData
    ) {
        $this->logger = $logger;
        $this->timezoneInterface = $timezoneInterface;
        $this->liquidoBrlSalesOrder = $liquidoBrlSalesOrder;
        $this->liquidoBrlSalesOrderResourceModel = $liquidoBrlSalesOrderResourceModel;
        $this->liquidoBrlSalesOrderCollection = $liquidoBrlSalesOrderCollection;
        $this->liquidoBrlConfigData = $liquidoBrlConfigData;
    }

    private function createNewLiquidoSalesOrder($orderData)
    {
        $liquidoBrlSalesOrder = $this->liquidoBrlSalesOrder;

        try {
            $liquidoBrlSalesOrder->setData("order_id", $orderData->getData("orderId"));
            $liquidoBrlSalesOrder->setData("idempotency_key", $orderData->getData("idempotencyKey"));
            $liquidoBrlSalesOrder->setData("transfer_status", $orderData->getData("transferStatus"));
            $liquidoBrlSalesOrder->setData("payment_method", $orderData->getData("paymentMethod"));

            $environment = $this->liquidoBrlConfigData->isProductionModeActived() ? "PRODUCTION" : "STAGING";
            $liquidoBrlSalesOrder->setData("environment", $environment);

            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $liquidoBrlSalesOrder->setData("created_at", $dateTimeNow);
            $liquidoBrlSalesOrder->setData("updated_at", $dateTimeNow);

            $this->liquidoBrlSalesOrderResourceModel->save($liquidoBrlSalesOrder);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function findLiquidoSalesOrderByIdempotencyKey($idempotencyKey)
    {
        try {
            $foundLiquidoSalesOrder = $this->liquidoBrlSalesOrderCollection
                ->addFieldToFilter('idempotency_key', $idempotencyKey)
                ->getFirstItem();
            return $foundLiquidoSalesOrder;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function findLiquidoSalesOrderByOrderId($orderId)
    {
        try {
            $foundLiquidoSalesOrder = $this->liquidoBrlSalesOrderCollection
                ->addFieldToFilter('order_id', $orderId)
                ->getFirstItem();
            return $foundLiquidoSalesOrder;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAlreadyRegisteredIdempotencyKey($orderId)
    {

        $foundLiquidoSalesOrder = $this->findLiquidoSalesOrderByOrderId($orderId);

        $liquidoSalesOrderAlreadyExists = $foundLiquidoSalesOrder->getData('order_id') != null;
        $liquidoSalesOrderAlreadyExistsAndResponseFailed = $liquidoSalesOrderAlreadyExists
            && ($foundLiquidoSalesOrder->getData('transfer_status') == null
                || $foundLiquidoSalesOrder->getData('transfer_status') == PayInStatus::FAILED
            );

        if ($liquidoSalesOrderAlreadyExists && !$liquidoSalesOrderAlreadyExistsAndResponseFailed) {
            $liquidoIdempotencyKey = $foundLiquidoSalesOrder->getData('idempotency_key');
            return $liquidoIdempotencyKey;
        }

        return null;
    }

    private function updateLiquidoSalesOrderIdempotencyKey($foundLiquidoSalesOrder, $liquidoIdempotencyKey)
    {
        try {
            $foundLiquidoSalesOrder->setData("idempotency_key", $liquidoIdempotencyKey);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundLiquidoSalesOrder->setData("updated_at", $dateTimeNow);
            $this->liquidoBrlSalesOrderResourceModel->save($foundLiquidoSalesOrder);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateLiquidoSalesOrderStatus($foundLiquidoSalesOrder, $newTransferStatus)
    {
        try {
            $foundLiquidoSalesOrder->setData("transfer_status", $newTransferStatus);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundLiquidoSalesOrder->setData("updated_at", $dateTimeNow);
            $this->liquidoBrlSalesOrderResourceModel->save($foundLiquidoSalesOrder);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateLiquidoSalesOrderPaymentMethod($foundLiquidoSalesOrder, $newPaymentMethod)
    {
        try {
            $foundLiquidoSalesOrder->setData("payment_method", $newPaymentMethod);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundLiquidoSalesOrder->setData("updated_at", $dateTimeNow);
            $this->liquidoBrlSalesOrderResourceModel->save($foundLiquidoSalesOrder);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function createOrUpdateLiquidoSalesOrder($orderData)
    {

        $className = static::class;
        $this->logger->info("[ {$className} ]: Database data:", (array) $orderData);

        try {

            $orderId = $orderData->getData("orderId");
            $idempotencyKey = $orderData->getData("idempotencyKey");
            $transferStatus = $orderData->getData("transferStatus");
            $paymentMethod = $orderData->getData("paymentMethod");

            $foundLiquidoSalesOrder = $this->findLiquidoSalesOrderByOrderId($orderId);
            $liquidoSalesOrderAlreadyExists = $foundLiquidoSalesOrder->getData('order_id') != null;

            /** -------------- Liquido Sales Order ("liquido_payin_sales_order" table)-------------- */
            if (!$liquidoSalesOrderAlreadyExists) {
                $this->logger->info("[ {$className} ]: Creating a new register in liquido_payin_sales_order table");
                $this->createNewLiquidoSalesOrder($orderData);
            } else {

                $this->logger->info("[ {$className} ]: Updating a register in liquido_payin_sales_order table (Liquio BR module table)");

                if ($foundLiquidoSalesOrder->getData('idempotency_key') != $idempotencyKey) {
                    $this->updateLiquidoSalesOrderIdempotencyKey($foundLiquidoSalesOrder, $idempotencyKey);
                }

                if ($foundLiquidoSalesOrder->getData('transfer_status') != $transferStatus) {
                    $this->updateLiquidoSalesOrderStatus($foundLiquidoSalesOrder, $transferStatus);
                }

                if ($foundLiquidoSalesOrder->getData('payment_method') != $paymentMethod) {
                    $this->updateLiquidoSalesOrderPaymentMethod($foundLiquidoSalesOrder, $paymentMethod);
                }
            }
            /** -------------- Liquido Sales Order -------------- */

            /** -------------- Magento Sales Order ("sales_order" table) -------------- */
            $this->logger->info("[ {$className} ]: Updating a register in sales_order table (Magento core table)");

            $magentoSalesOrder = MagentoSalesOrder::findOrder($orderId);
            $magentoOrderStatus = LiquidoBrlPayInStatus::mapToMagentoSaleOrderStatus($transferStatus);
            if ($magentoSalesOrder->getStatus() != $magentoOrderStatus) {
                MagentoSalesOrder::updateOrderStatus($magentoSalesOrder, $magentoOrderStatus);
            }
            /** -------------- Magento Sales Order -------------- */
        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while trying saving on database");
            $this->logger->error($e->getMessage());
        }
    }
}
