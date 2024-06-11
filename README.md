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
- <a href="https://stripe.com" target="_blank">Stripe Account</a>
- <a href="https://github.com/craftcms/cms" target="_blank">Craft CMS ^4.7.0</a>
- PHP >=8.0.2
- <a href="https://github.com/craftmcs/commerce" target="_blank">Craft Commerce ^4.0.0</a>
- <a href="https://github.com/craftcms/commerce-stripe" target="_blank">Craft Commerce Stripe ^4.0.0</a>

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

### Products page
![Product Page](https://i.imgur.com/Wxafzai.png)
**`products/_product.twig`**
```twig
{{ craft.expressCheckout.buttons({
    items: [{
      id: product.defaultVariant.id,
      qty: 1,
    }]
}) | raw }}
```
We pass an array of `items` to the `craft.expressCheckout.buttons` function.

### Cart page
![Cart Page](https://i.imgur.com/X8wQrOm.png)
**`cart/cart.twig`**
```twig
{{ craft.expressCheckout.buttons({ 
  cart: craft.commerce.carts.cart,
  cancelUrl: '/cart',
 }) | raw }}
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

### Plugin Settings
You can configure all settings in the plugin settings page. All settings can be overridden by passing them to the
`craft.expressCheckout.buttons` function.

| Setting Name | Description |
| --- | --- |
| gatewayId | Select the gateway through which payments will be processed. |
| inventoryId | Select an inventory. |
| shippingAddressRequired | Checking this will prompt the user for their shipping address. |
| phoneNumberRequired | Checking this box will prompt the user for their phone number. |
| restrictCountries | Checking this will restrict the Stripe 'allowedCountries' to your country list in Commerce -> Store Settings -> Store -> Country List. |
| successUrl | The URL that Stripe redirects to after a successful transaction. The 'successUrl' gets a query parameter called 'number' that contains the order number. |
| cancelUrl | The URL that Stripe will redirect to after the user abandons the checkout process. By default, this is the URL the user came from. |
| loaderTemplate | The path to the loader template. |
| buttonHeight | The height of the buttons. |
| applePayTheme | The theme of the Apple Pay button. |
| googlePayTheme | The theme of the GooglePay button. |
| paypalTheme | The theme of the PayPal button. |
| applePayType | The type of the Apple Pay button. |
| googlePayType | The type of the GooglePay button. |
| paypalType | The type of the PayPal button. |
| maxColumns | The maximum number of columns. |
| maxRows | The maximum number of rows. |
| overflow | Choose how the overflow should be handled. |
| paymentMethodOrder | The order of the payment methods. |
| showApplePay | Show the Apple Pay button. |
| showGooglePay | Show the GooglePay button. |
| phoneField | Map the phone number to plain text on the order field layout. This will only take effect if you enable 'Phone Number Required'. |

### Event Hooks
| Klasse | Event |
| --- | --- |
| StripeController | EVENT_BEFORE_UPDATE_SHIPPING_RATE |
| StripeController | EVENT_AFTER_UPDATE_SHIPPING_RATE |
| StripeController | EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE |
| StripeController | EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE |
| ProcessStripeWebhook | EVENT_MODIFY_ORDER_DETAILS |
| ProcessStripeWebhook | EVENT_BEFORE_ORDER_COMPLETE |
| ProcessStripeWebhook | EVENT_AFTER_ORDER_COMPLETE |
| ProcessStripeWebhook | EVENT_BEFORE_SAVE_SHIPPING_ADDRESS |
| ProcessStripeWebhook | EVENT_AFTER_SAVE_SHIPPING_ADDRESS |
| ProcessStripeWebhook | EVENT_BEFORE_SAVE_BILLING_ADDRESS |
| ProcessStripeWebhook | EVENT_AFTER_SAVE_BILLING_ADDRESS |
| ProcessStripeWebhook | EVENT_BEFORE_SET_ORDER_CUSTOMER |
| ProcessStripeWebhook | EVENT_WEBHOOK_FAILED |
| ProcessStripeWebhook | EVENT_RECEIVED_WEBHOOK |

## Adjustments made for Craft Commerce
- Email is always required
