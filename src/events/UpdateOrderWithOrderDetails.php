<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

class UpdateOrderWithOrderDetails extends Event
{
    public function __construct(
        public Order $order,
        public array $metadata,
        $config = []
    )
    {
        parent::__construct($config);
    }

}