<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model\Brl;

use \Magento\Framework\Model\AbstractModel;

use \Liquido\PayIn\Model\Brl\ResourceModel\LiquidoBrlSalesOrder as LiquidoBrlSalesOrderResourceModel;

class LiquidoBrlSalesOrder extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(LiquidoBrlSalesOrderResourceModel::class);
    }
}
