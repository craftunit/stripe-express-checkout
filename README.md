# Stripe Express Checkout
Adding the [Stripe Express Checkout Element](https://docs.stripe.com/elements/express-checkout-element) to Commerce-Stripe.

## Table of Contents
- [Showcase](#showcase)
- [Requirements](#requirements)
- [Installation](#installation)
- [Features](#features)
- [Usage](#usage)
  - [1. Products page](#1-products-page)
  - [2. Cart page](#2-cart-page)
  - [Configuration](#configuration)
    - [Stripe Gateway](#stripe-gateway)
    - [Commerce](#commerce)
    - [Button Options](#button-options)
    - [Event Hooks](#event-hooks)
  - [Adjustments made for Craft Commerce](#adjustments-made-for-craft-commerce)

## Showcase
**TODO: Add gif/webm of the express checkout; Maybe a YouTube Video showcasing the plugin would be neat**

## Requirements
- Craft CMS ^4.7.0
- PHP >=8.0.2
- Craft Commerce ^4.0.0
- Craft Commerce Stripe ^4.0.0

## Installation
You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store
Go to the Plugin Store in your project’s Control Panel and search for “Stripe Express Checkout”. Then press “Install”.

#### With Composer
Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require craft-unit/craft-stripe-express-checkout

# tell Craft to install the plugin
./craft plugin/install stripe-express-checkout
```

## Features
- Adds the Stripe Express Checkout Element to your Craft Commerce site.
- Checkout products and carts with a single click.
- Supports Apple Pay, Google Pay, PayPal and much more.
- Supports shipping address and phone number requirements.
- Supports all the options from the Stripe Express Checkout Element.
- Intercept request through event hooks.
- Automatically adjusts costs based on configured shipping rules.
- Restrict deliveries to allowed countries in your Commerce settings.

## Usage

### Before you start
Before you can use this plugin, you need to install the Stripe Gateway Plugin and configure it. All orders
will be processed through the selected gateway. You can find the Stripe Gateway Plugin in the Plugin Store. Then you can
configure set the Gateway in the plugin settings.

#### Global Settings
You can configure all settings in the plugin settings page.
![Stripe Express Checkout Settings Page](https://i.imgur.com/t0LXpSU.png)

#### Local settings
You can also pass settings directly to the `craft.expressCheckout.buttons` function. This will override the global settings.
```twig
{# EXAMPLE SETTINGS #}
{% set settings  = {
    name: product.slug ~ '-express-checkout',

    shippingAddressRequired: true,
    phoneNumberRequired: false,

    paymentMethodOrder: ['paypal', 'googlePay', 'link'],
    buttonTheme: {
      paypal: 'blue',
    },

    buttonHeight: 55,
    buttonType: {
      paypal: 'pay',
      googlePay: 'order',
    },
    wallets: {
      applePay: 'always',
      googlePay: 'always',
    }
    {# and more... #}
} %}
```

### Rendering the buttons
You render the buttons by using the `craft.expressCheckout.buttons` function. You then pass an array of options to the
function. All options you pass the button will be passed through to the Stripe API. You can find all the options in the
[Stripe documentation](https://docs.stripe.com/js/element/express_checkout_element). Every option you see there can be used.

#### 1. Products page
**TODO: Add image of product page**
**`products/_product.twig`**
```twig
{{ craft.expressCheckout.buttons({
    items: [{
      id: variant.id,
      qty: 1,
    }]
}) | raw }}
```
We pass an array of `items` to the `craft.expressCheckout.buttons` function.

### 2. Cart page
**TODO: Add image of cart page**
**`cart/cart.twig`**
```twig
{{ craft.expressCheckout.buttons({ cart }) | raw }}
```
In this example we pass our cart object directly to the `craft.expressCheckout.buttons` function. This is useful for using
express checkout on the cart page.

## Order complete (TODO: Redirect nur nach Success und webhook von stripe?)
After the order is completed, the user will be redirected to the `success_url` you set in the options. At this point in time
your order might not be completed in Craft Commerce hence why you won't see the completed order. You can use  `fetch` to 
ask for the order and return it to the success page.

## Configuration
### Stripe Gateway
After installing the plugin, go to the plugin settings page and select your configured Stripe gateway. This plugin will
then pass everything to the selected gateway.

### Commerce (TODO: Brauchen wir das noch? In meinem craftshop brauch ich kein autosetcartshipping)
It is recommended to set `autoSetCartShippingMethodOption` to `true` in `/config/commerce.php`. This ensures a
shipping method is set on your order.
```php
<?php

return [
    'autoSetCartShippingMethodOption' => true,
];
```
### Event Hooks
**TODO: Add a list/table of all the events and usages**

## Adjustments made for Craft Commerce
- Email is always required
