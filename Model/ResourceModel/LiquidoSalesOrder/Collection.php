<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model\ResourceModel\LiquidoSalesOrder;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

use \Liquido\PayIn\Model\LiquidoSalesOrder;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesOrder as LiquidoSalesOrderResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(LiquidoSalesOrder::class, LiquidoSalesOrderResourceModel::class);
    }
}
