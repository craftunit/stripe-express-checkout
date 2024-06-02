<?php

namespace craftunit\craftstripeexpresscheckout\events;

use yii\base\Event;

class ModifyOrderDetailsEvent extends Event
{
    // TODO: Make class OrderDetails for easier access to order details
    public function __construct(public array &$orderDetails, $config = [])
    {
        parent::__construct($config);
    }
}
