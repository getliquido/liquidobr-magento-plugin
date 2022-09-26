<?php

namespace Liquido\PayIn\Util\Brl;

use \Liquido\PayIn\Util\MagentoSaleOrderStatus;
use \LiquidoBrl\PayInPhpSdk\Util\PayInStatus;

abstract class LiquidoBrlPayInStatus
{
  public static function mapToMagentoSaleOrderStatus($liquidoPayInStatus)
  {
    switch ($liquidoPayInStatus) {
      case PayInStatus::SETTLED:
        return MagentoSaleOrderStatus::COMPLETE;
        break;
      case PayInStatus::IN_PROGRESS:
        return MagentoSaleOrderStatus::PENDING_PAYMENT;
        break;
      case PayInStatus::CANCELLED || PayInStatus::FAILED:
        return MagentoSaleOrderStatus::CANCELLED;
        break;
      default:
        return MagentoSaleOrderStatus::PENDING_PAYMENT;
        break;
    }
  }
}
