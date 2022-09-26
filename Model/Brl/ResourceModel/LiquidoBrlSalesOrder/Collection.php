<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model\Brl\ResourceModel\LiquidoBrlSalesOrder;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

use \Liquido\PayIn\Model\Brl\LiquidoBrlSalesOrder;
use \Liquido\PayIn\Model\Brl\ResourceModel\LiquidoBrlSalesOrder as LiquidoBrlSalesOrderResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(LiquidoBrlSalesOrder::class, LiquidoBrlSalesOrderResourceModel::class);
    }
}
