<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\elements\User;
use yii\base\Event;

class UpdateOrderCustomerEvent extends Event
{
    public function __construct(public User $user, $config = [])
    {
        parent::__construct($config);
    }
}
