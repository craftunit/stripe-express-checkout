<?php

namespace craftunit\craftstripeexpresscheckout\web\assets\expresscheckout;

use craft\web\AssetBundle;

/**
 * Express Checkout asset bundle
 */
class ExpressCheckoutAsset extends AssetBundle
{
    public $sourcePath = __DIR__;
    public $js = [
        'js/main.js',
    ];
}
