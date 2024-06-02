<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum GooglePayTheme: string
{
    use AsOptions;

    case White = 'white';
    case Black = 'black';
}
