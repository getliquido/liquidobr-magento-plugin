<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model;

use \Magento\Framework\Model\AbstractModel;

use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesOrder as LiquidoSalesOrderResourceModel;

class LiquidoSalesOrder extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(LiquidoSalesOrderResourceModel::class);
    }
}
