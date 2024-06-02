<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum GooglePayType: string
{
    use AsOptions;

    case Book = 'book';
    case Buy = 'buy';
    case Checkout = 'checkout';
    case Donate = 'donate';
    case Order = 'order';
    case Pay = 'pay';
    case Plain = 'plain';
    case Subscribe = 'subscribe';
}
