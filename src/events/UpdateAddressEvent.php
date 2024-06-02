<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\elements\Address;
use yii\base\Event;

class UpdateAddressEvent extends Event
{
    public function __construct(public readonly Address $address, $config = [])
    {
        parent::__construct($config);
    }
}
