<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum PaypalType: string
{
    use AsOptions;

    case Paypal = 'paypal';
    case Checkout = 'checkout';
    case BuyNow = 'buynow';
    case Pay = 'pay';
}
