<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum ApplePayTheme: string
{
    use AsOptions;

    case White = 'white';
    case Black = 'black';
    case WhiteOutline = 'white-outline';
}
