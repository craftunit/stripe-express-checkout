<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;
use yii\base\Exception;

class WebhookFailedEvent extends Event
{
    // TODO: Find a better solution for handling errors;
    public function __construct(
        public Order| null $order,
        public string $errorMessage,
        public ?Exception $exception = null,
        $config = [],
    ) {
        parent::__construct($config);
    }
}
