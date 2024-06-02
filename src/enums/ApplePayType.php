<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum ApplePayType: string
{
    use AsOptions;

    case AddMoney = 'add-money';
    case Book = 'book';
    case Buy = 'buy';
    case CheckOut = 'check-out';
    case Continue = 'continue';
    case Contribute = 'contribute';
    case Donate = 'donate';
    case Order = 'order';
    case Plain = 'plain';
    case Reload = 'reload';
    case Rent = 'rent';
    case Subscribe = 'subscribe';
    case Tip = 'tip';
    case TopUp = 'top-up';
}
