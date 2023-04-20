<?php

namespace Liquido\PayIn\Helper;

use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;

use \Liquido\PayIn\Model\LiquidoSalesCreditmemoGrid;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesCreditmemoGrid as LiquidoCreditmemoResourceModel;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesCreditmemoGrid\Collection as LiquidoCreditmemoCollection;
use \Liquido\PayIn\Model\MagentoSalesOrder;
use \Liquido\PayIn\Util\LiquidoPayInStatus;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;

class LiquidoCreditmemoHelper extends AbstractHelper
{
    private LoggerInterface $logger;
    private TimezoneInterface $timezoneInterface;

    private LiquidoSalesCreditmemoGrid $liquidoSalesCreditmemoGrid;
    private LiquidoCreditmemoResourceModel $liquidoCreditmemoResourceModel;
    private LiquidoCreditmemoCollection $liquidoCreditmemoCollection;
    private LiquidoConfigData $liquidoConfigData;

    public function __construct(
        LoggerInterface $logger,
        TimezoneInterface $timezoneInterface,
        LiquidoSalesCreditmemoGrid $liquidoSalesCreditmemoGrid,
        LiquidoCreditmemoResourceModel $liquidoCreditmemoResourceModel,
        LiquidoCreditmemoCollection $liquidoCreditmemoCollection,
        LiquidoConfigData $liquidoConfigData
    )
    {
        $this->logger = $logger;
        $this->timezoneInterface = $timezoneInterface;
        $this->liquidoSalesCreditmemoGrid = $liquidoSalesCreditmemoGrid;
        $this->liquidoCreditmemoResourceModel = $liquidoCreditmemoResourceModel;
        $this->liquidoCreditmemoCollection = $liquidoCreditmemoCollection;
        $this->liquidoConfigData = $liquidoConfigData;
    }

    private function createNewCreditmemo($creditmemoData)
    {
        $liquidoSalesCreditmemoGrid = $this->liquidoSalesCreditmemoGrid;

        try {
            $liquidoSalesCreditmemoGrid->setData("order_id", $creditmemoData->getData("orderId"));
            $liquidoSalesCreditmemoGrid->setData("idempotency_key", $creditmemoData->getData("idempotencyKey"));
            $liquidoSalesCreditmemoGrid->setData("creditmemo_id", $creditmemoData->getData("creditmemoId"));
            $liquidoSalesCreditmemoGrid->setData("reference_id", $creditmemoData->getData("referenceId"));
            $liquidoSalesCreditmemoGrid->setData("transfer_status", $creditmemoData->getData("transferStatus"));

            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $liquidoSalesCreditmemoGrid->setData("created_at", $dateTimeNow);
            $liquidoSalesCreditmemoGrid->setData("updated_at", $dateTimeNow);

            $this->liquidoCreditmemoResourceModel->save($liquidoSalesCreditmemoGrid);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function findCreditmemoByRefundIdempotencyKey($idempotencyKey)
    {
        try {
            $foundLiquidoCreditmemo = $this->liquidoCreditmemoCollection
                ->addFieldToFilter('idempotency_key', $idempotencyKey)
                ->getFirstItem();
            return $foundLiquidoCreditmemo;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function findCreditmemoByCreditmemoId($creditmemoId)
    {
        try {
            $foundLiquidoCreditmemo = $this->liquidoCreditmemoCollection
                ->addFieldToFilter('creditmemo_id', $creditmemoId)
                ->getFirstItem();
            $this->logger->info("findLiquidoCreditmemoByCreditmemoId", (array) $foundLiquidoCreditmemo->getData('creditmemo_id'));
            return $foundLiquidoCreditmemo;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAlreadyRegisteredRefundIdempotencyKey($idempotencyKey)
    {
        $this->logger->info("getAlreadyRegisteredRefundIdempotencyKey");

        $foundCreditmemo = $this->findCreditmemoByRefundIdempotencyKey($idempotencyKey);

        $creditmemoAlreadyExists = $foundCreditmemo->getData('order_id') != null;
        $creditmemoAlreadyExistsAndResponseFailed = $creditmemoAlreadyExists
            && ($foundCreditmemo->getData('transfer_status') == null
                || $foundCreditmemo->getData('transfer_status') == PayInStatus::FAILED
            );
            
        if ($creditmemoAlreadyExists && !$creditmemoAlreadyExistsAndResponseFailed) {
            $liquidoRefundIdempotencyKey = $foundCreditmemo->getData('idempotency_key');
            return $liquidoRefundIdempotencyKey;
        }

        return null;
    }

    private function updateCreditmemoIdempotencyKey($foundCreditmemo, $idempotencyKey)
    {
        try {
            $foundCreditmemo->setData("idempotency_key", $idempotencyKey);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundCreditmemo->setData("updated_at", $dateTimeNow);
            $this->liquidoCreditmemoResourceModel->save($foundCreditmemo);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateCreditmemoReferenceId($foundCreditmemo, $referenceId)
    {
        try {
            $foundCreditmemo->setData("reference_id", $referenceId);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundCreditmemo->setData("updated_at", $dateTimeNow);
            $this->liquidoCreditmemoResourceModel->save($foundCreditmemo);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateCreditmemoId($foundCreditmemo, $creditmemoId)
    {
        try {
            $foundCreditmemo->setData("creditmemo_id", $creditmemoId);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundCreditmemo->setData("updated_at", $dateTimeNow);
            $this->liquidoCreditmemoResourceModel->save($foundCreditmemo);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function updateCreditmemoStatus($foundCreditmemo, $newTransferStatus)
    {
        try {
            $foundCreditmemo->setData("transfer_status", $newTransferStatus);
            $dateTimeNow = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $foundCreditmemo->setData("updated_at", $dateTimeNow);
            $this->liquidoCreditmemoResourceModel->save($foundCreditmemo);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function createOrUpdateLiquidoCreditmemo($creditmemoData)
    {
        $className = static::class;
        $this->logger->info("[ {$className} ]: Database data:", (array) $creditmemoData);

        try {
            $orderId = $creditmemoData->getData("orderId");
            $creditmemoId = $creditmemoData->getData("creditmemoId");
            $idempotencyKey = $creditmemoData->getData("idempotencyKey");
            $referenceId = $creditmemoData->getData("referenceId");
            $transferStatus = $creditmemoData->getData("transferStatus");

            $foundCreditmemo = $this->findCreditmemoByRefundIdempotencyKey($idempotencyKey);

            $liquidoCreditmemoAlreadyExists = $foundCreditmemo->getData('idempotency_key') != null;

            /**************************** Liquido Credit Memo ("liquido_payin_sales_creditmemo_grid table") *****************************/
            if (!$liquidoCreditmemoAlreadyExists) {
                $this->logger->info("[ {$className} ]: Creating a new register in liquido_payin_sales_creditmemo_grid table");
                $this->createNewCreditmemo($creditmemoData);
            } else {
                $this->logger->info("[ {$className} ]: Updating a register in liquido_payin_sales_creditmemo_grid table (Liquio BR module table)");

                if ($foundCreditmemo->getData('idempotency_key') != $idempotencyKey) {
                    $this->updateCreditmemoIdempotencyKey($foundCreditmemo, $idempotencyKey);
                }

                if ($foundCreditmemo->getData('reference_id') != $referenceId) {
                    $this->updateCreditmemoReferenceId($foundCreditmemo, $referenceId);
                }

                if ($foundCreditmemo->getData('creditmemo_id') != $creditmemoId) {
                    $this->updateCreditmemoId($foundCreditmemo, $creditmemoId);
                }

                if ($foundCreditmemo->getData('transfer_status') != $transferStatus) {
                    $this->updateCreditmemoStatus($foundCreditmemo, $transferStatus);
                }
            }
            /*********************************** Liquido Credit Memo ****************************************/

        } catch (\Exception $e) {
            $this->logger->error("[ {$className} ]: Error while trying saving on database");
            $this->logger->error($e->getMessage());
        }
    }
}