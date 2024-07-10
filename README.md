# Stripe Express Checkout
Adding the [Stripe Express Checkout Element](https://docs.stripe.com/elements/express-checkout-element) to Commerce-Stripe.

## Table of Contents
- [Features](#features)
- [Showcase](#showcase)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [1. Products page](#1-products-page)
  - [2. Cart page](#2-cart-page)
  - [Configuration](#configuration)
    - [Stripe Gateway](#stripe-gateway)
    - [Commerce](#commerce)
    - [Button Options](#button-options)
    - [Event Hooks](#event-hooks)
  - [Adjustments made for Craft Commerce](#adjustments-made-for-craft-commerce)

## Features
- Adds the Stripe Express Checkout Element to your Craft Commerce site.
- Checkout products and carts with a single click.
- Supports Apple Pay, Google Pay, PayPal and much more.
- Supports shipping address and phone number requirements.
- Supports all the options from the Stripe Express Checkout Element.
- Intercept request through event hooks.
- Automatically adjusts costs based on configured shipping rules.
- Restrict deliveries to allowed countries in your Commerce settings.

## Showcase
**TODO: Add gif/webm of the express checkout; Maybe a YouTube Video showcasing the plugin would be neat**

## Requirements
- <a href="https://stripe.com" target="_blank">Stripe Account</a>
- <a href="https://github.com/craftcms/cms" target="_blank">Craft CMS ^4.7.0</a>
- PHP >= 8.2
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

## Setup
1. Install [Craft Commerce](https://github.com/craftcms/commerce)  and the [Stripe Gateway Plugin](https://github.com/craftcms/commerce-stripe).
2. Create a [Stripe Account](https://stripe.com) or use an existing one.
3. Create a new Gateway under Commerce -> System Settings -> Gateways. See [Stripe Gateway Documentation](https://github.com/craftcms/commerce-stripe?tab=readme-ov-file#setup).
   - Set the Gateway Type to Stripe.
   - Set the Publishable Key and Secret Key.
   - After saving the gateway, you get a webhook endpoint. You will need to create a new webhook in your Stripe account. This can be a local endpoint or a live endpoint. Follow the instructions in your Stripe account.
4. Go to the plugin settings in the sidebar and select the gateway you just created.
5. Render the buttons on your products and cart page.
6. Be sure to handle the order complete event. You can use the `fetch` API to ask for the order and return it to the success page.

### Products page
**`products/_product.twig`**
```twig 
{{ craft.expressCheckout.buttons({
    items: [{
      id: product.defaultVariant.id,
      qty: 1,
    }],
}) | raw }}
```
![Product Page](https://i.imgur.com/Wxafzai.png)

### Cart page
**`cart.twig`**
```twig
{{ craft.expressCheckout.buttons({ 
  cart: craft.commerce.carts.cart,
 }) | raw }}
```
![Cart Page](https://i.imgur.com/X8wQrOm.png)

## Configuration
Configuration can be done globally in the plugin settings or locally by passing settings to the `craft.expressCheckout.buttons` function. Passing the settings to the function will override the global settings.
You can find all the options in the [Stripe documentation](https://docs.stripe.com/js/element/express_checkout_element).

| Setting Name | Description |
| --- | --- |
| gatewayId | Select the gateway through which payments will be processed. |
| shippingAddressRequired | Checking this will prompt the user for their shipping address. |
| phoneNumberRequired | Checking this box will prompt the user for their phone number. |
| restrictCountries | Checking this will restrict the Stripe 'allowedCountries' to your country list in Commerce -> Store Settings -> Store -> Country List. |
| successUrl | The URL that Stripe redirects to after a successful transaction. The 'successUrl' gets a query parameter called 'number' that contains the order number. |
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



### Global Settings
![Stripe Express Checkout Settings Page](https://i.imgur.com/UooyyGh.png)

### Local settings
```twig
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

### Event Hooks
| Class                | Event |
|----------------------| --- |
| StripeController     | EVENT_BEFORE_UPDATE_SHIPPING_RATE |
| StripeController     | EVENT_AFTER_UPDATE_SHIPPING_RATE |
| StripeController     | EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE |
| StripeController     | EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE |
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


## Order complete (TODO: Redirect nur nach Success und webhook von stripe?)
After the order is completed, the user will be redirected to the `success_url` you set in the options. At this point in time
your order might not be completed in Craft Commerce hence why you won't see the completed order. You can use  `fetch` to 
ask for the order and return it to the success page.

## Adjustments made for Craft Commerce
- Email is always required
