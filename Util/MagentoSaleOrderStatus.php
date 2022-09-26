<?php

namespace Liquido\PayIn\Util;

class MagentoSaleOrderStatus
{
    public const CANCELLED = 'canceled';
    public const CLOSED = 'closed';
    public const COMPLETE = 'complete';
    public const SUSPECTED_FRAUD = 'fraud';
    public const ON_HOLD = 'holded';
    public const PAYMENT_REVIEW = 'payment_review';
    public const PENDING = 'pending';
    public const PENDING_PAYMENT = 'pending_payment';
    public const PROCESSING = 'processing';
}
