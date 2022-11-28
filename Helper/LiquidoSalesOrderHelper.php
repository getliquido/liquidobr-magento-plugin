<?php

namespace Liquido\PayIn\Helper;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Model\LiquidoSalesOrder;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesOrder as LiquidoSalesOrderResourceModel;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesOrder\Collection as LiquidoSalesOrderCollection;
use \Liquido\PayIn\Model\MagentoSalesOrder;
use \Liquido\PayIn\Util\LiquidoPayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;

class LiquidoSalesOrderHelper extends AbstractHelper
{

    private LoggerInterface $logger;
    private TimezoneInterface $timezoneInterface;
    private LiquidoSalesOrder $liquidoSalesOrder;
    private LiquidoSalesOrderResourceModel $liquidoSalesOrderResourceModel;
    private LiquidoSalesOrderCollection $liquidoSalesOrderCollection;
    private LiquidoConfigData $liquidoConfigData;

    public function __construct(
        LoggerInterface $logger,
        TimezoneInterface $timezoneInterface,
        LiquidoSalesOrder $liquidoSalesOrder,
        LiquidoSalesOrderResourceModel $liquidoSalesOrderResourceModel,
        LiquidoSalesOrderCollection $liquidoSalesOrderCollection,
        LiquidoConfigData $liquidoConfigData
    ) {
        $this->logger = $logger;
        $this->timezoneInterface = $timezoneInterface;
        $this->liquidoSalesOrder = $liquidoSalesOrder;
        $this->liquidoSalesOrderResourceModel = $liquidoSalesOrderResourceModel;
        $this->liquidoSalesOrderCollection = $liquidoSalesOrderCollection;
        $this->liquidoConfigData = $liquidoConfigData;
    }

    private function createNewLiquidoSalesOrder($orderData)
    {
        $liquidoSalesOrder = $this->liquidoSalesOrder;

        try {
            $liquidoSalesOrder->setData("order_id", $orderData->getData("orderId"));
            $liquidoSalesOrder->setData("idempotency_key", $orderData->getData("idempotencyKey"));
            $liquidoSalesOrder->setData("transfer_status", $orderData->getData("transferStatus"));
            $liquidoSalesOrder->setData("payment_method", $orderData->getData("paymentMethod"));

            $environment = $this->liquidoConfigData->isProductionModeActived() ? "PRODUCTION" : "STAGING";
            $liquidoSalesOrder->setData("environment", $environment);

            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $liquidoSalesOrder->setData("created_at", $dateTimeNow);
            $liquidoSalesOrder->setData("updated_at", $dateTimeNow);

            $this->liquidoSalesOrderResourceModel->save($liquidoSalesOrder);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function findLiquidoSalesOrderByIdempotencyKey($idempotencyKey)
    {
        try {
            $foundLiquidoSalesOrder = $this->liquidoSalesOrderCollection
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
            $foundLiquidoSalesOrder = $this->liquidoSalesOrderCollection
                ->addFieldToFilter('order_id', $orderId)
                ->getFirstItem();
            return $foundLiquidoSalesOrder;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAlreadyRegisteredIdempotencyKey($orderId)
    {
        $this->logger->info("getAlreadyRegisteredIdempotencyKey");

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
            $this->liquidoSalesOrderResourceModel->save($foundLiquidoSalesOrder);
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
            $this->liquidoSalesOrderResourceModel->save($foundLiquidoSalesOrder);
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
            $this->liquidoSalesOrderResourceModel->save($foundLiquidoSalesOrder);
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
            $magentoOrderStatus = LiquidoPayInStatus::mapToMagentoSaleOrderStatus($transferStatus);
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
