<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum PaypalTheme: string
{
    use AsOptions;

    case White = 'white';
    case Black = 'black';
    case Gold = 'gold';
    case Blue = 'blue';
    case Silver = 'silver';
}
