<?php

namespace craftunit\craftstripeexpresscheckout\events;

use yii\base\Event;

class ReceiveStripeWebhookEvent extends Event
{
    public function __construct(public array &$webhookData, $config = [])
    {
        parent::__construct($config);
    }
}
