<?php

namespace craftunit\craftstripeexpresscheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Fired right before the express checkout buttons are rendered, giving host
 * projects a chance to modify the options that are passed to the client side
 * `StripeExpressCheckout.init()` call (and thus to `stripe.elements()` /
 * the Express Checkout Element).
 *
 * Typical use case: set a `paymentMethodConfiguration` (PMC) so the express
 * flow shows a different set of payment methods than the regular onsite
 * checkout.
 */
class ModifyButtonOptionsEvent extends Event
{
    /**
     * @param array $options The options passed to `StripeExpressCheckout.init()`. Mutable by reference.
     * @param Order|null $order The order (cart) the buttons are rendered for.
     */
    public function __construct(
        public array &$options,
        public ?Order $order = null,
        $config = []
    ) {
        parent::__construct($config);
    }
}
