<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\elements\Address;
use yii\base\Event;

class UpdateAddressEvent extends Event
{
    public function __construct(
        public Address $address,
        public array $addressData,
        $config = [])
    {
        parent::__construct($config);
    }
}
