<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum Overflow: string
{
    use AsOptions;
    case Auto = 'auto';
    case Hidden = 'hidden';
}
