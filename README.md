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

**TODO: talk about some options like setting the phone number or something**

## Usage
You render the buttons by using the `craft.expressCheckout.buttons` function. You then pass an array of options to the
function. All options you pass the button will be passed through to the Stripe API. You can find all the options in the
[Stripe documentation](https://docs.stripe.com/js/element/express_checkout_element). Every option you see there can be used.

There are two ways to use you can use the Stripe Express Checkout.

### 1. Products page
**TODO: Add image of product page**
**`products/_product.twig`**
```twig
{% set requiredParams = {
    items: [{
      id: variant.id,
      qty: 1,
    }],
    cancelUrl: '/products/' ~ product.slug,
    successUrl: alias('@web/success')
} %}

{% set optionalParams  = {
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
} %}

{% set params = requiredParams | merge(optionalParams) %}
{{ craft.expressCheckout.buttons(params) | raw }}
```
We pass an array of `items` to the `craft.expressCheckout.buttons` function. Each item needs an id and a quantity. You
will also need to pass a `cancelUrl` and a `successUrl`. Your `successUrl` needs to be an absolute path (TODO: not make absolute).

### 2. Cart page
**TODO: Add image of cart page**
**`cart/cart.twig`**
```twig
{{ craft.expressCheckout.buttons({
  cart,
  shippingAddressRequired: true,
  cancelUrl: '/cart',
  successUrl: alias('@web/success?number=' ~ cart.number),
  wallets: {
    applePay: 'always',
    googlePay: 'always',
  }
}) | raw }}
```
In this example we pass our cart object directly to the `craft.expressCheckout.buttons` function. This is useful for using
express checkout on the cart page.

## Configuration
### Stripe Gateway
After installing the plugin, go to the plugin settings page and select your configured Stripe gateway. This plugin will
then pass everything to the selected gateway.

### Commerce 
It is recommended to set `autoSetCartShippingMethodOption` to `true` in `/config/commerce.php`. This ensures a
shipping method is set on your order.
```php
<?php

return [
    'autoSetCartShippingMethodOption' => true,
];
```
### Button Options
**TODO: Add a list/table of all the options**

### Event Hooks
**TODO: Add a list/table of all the events and usages**

## Adjustments made for Craft Commerce
- Email is always required
