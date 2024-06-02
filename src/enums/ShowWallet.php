<?php

namespace craftunit\craftstripeexpresscheckout\enums;

use craftunit\craftstripeexpresscheckout\traits\AsOptions;

enum ShowWallet: string
{
    use AsOptions;

    case Always = 'always';
    case Auto = 'auto';
    case Never = 'never';
}
