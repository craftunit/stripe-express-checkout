<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

class UpdateOrderEvent extends Event
{
    public function __construct(public readonly Order $order, $config = [])
    {
        parent::__construct($config);
    }
}
