<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model\ResourceModel\LiquidoSalesCreditmemoGrid;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

use \Liquido\PayIn\Model\LiquidoSalesCreditmemoGrid;
use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesCreditmemoGrid as LiquidoCreditmemoResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(LiquidoSalesCreditmemoGrid::class, LiquidoCreditmemoResourceModel::class);
    }
}
