<?php

declare(strict_types=1);

namespace Liquido\PayIn\Model;

use \Magento\Framework\Model\AbstractModel;

use \Liquido\PayIn\Model\ResourceModel\LiquidoSalesCreditmemoGrid as LiquidoCreditmemoResourceModel;

class LiquidoSalesCreditmemoGrid extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(LiquidoCreditmemoResourceModel::class);
    }
}
