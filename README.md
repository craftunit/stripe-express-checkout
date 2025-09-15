# Stripe Express Checkout

Adding the [Stripe Express Checkout Element](https://docs.stripe.com/elements/express-checkout-element) to
Commerce-Stripe.

## Table of Contents

- [Features](#features)
- [Showcase](#showcase)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
    - [Products page](#products-page)
    - [Cart page](#cart-page)
    - [Order complete](#order-complete)
- [Configuration](#configuration)
    - [Global Settings](#global-settings)
    - [Local Settings](#local-settings)
- [Extending and Customizing](#extending-and-customizing)
    - [Event Hooks](#event-hooks)
    - [Frontend JS](#frontend-js)
        - [Events](#events)
        - [Public Methods](#public-methods)
        - [Example](#example)

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

- PHP >= 8.2
- <a href="https://stripe.com" target="_blank">Stripe Account</a>
- <a href="https://github.com/craftcms/cms" target="_blank">Craft CMS ^5.0.0</a>
- <a href="https://github.com/craftcms/commerce" target="_blank">Craft Commerce ^5.0.0</a>
- <a href="https://github.com/craftcms/commerce-stripe" target="_blank">Craft Commerce Stripe ^5.0.0</a>

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

e
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

1. Create a [Stripe Account](https://stripe.com) or use an existing one.
2. Install [Craft Commerce](https://github.com/craftcms/commerce) and
   the [Stripe Gateway Plugin](https://github.com/craftcms/commerce-stripe).
3. Follow the [setup](https://github.com/craftcms/commerce-stripe#setup) instruction for
   the [Stripe Gateway Plugin](https://github.com/craftcms/commerce-stripe).
4. Go to the plugin settings in the sidebar and select the gateway you just created.
5. Render the buttons on your products and cart page.
6. Be sure to handle the order complete event. You can use the `fetch` API to ask for the order and return it to the
   success page. See the [Order complete](#order-complete) section for more information.

### Products page

**`products/_product.twig`**

```twig 
{{ craft.expressCheckout.buttons({
    itemsId: product.defaultVariant.id
}) | raw }}

{# or... #}

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

### Order complete

After the order is completed, the user will be redirected to the `success_url` you set in the options. At this point in
time your order might not be completed in Craft Commerce hence why you won't see the completed order. You can
use  `fetch` to
ask for the order and return it to the success page. Use the `number` query parameter to get the order number.

```javascript
// Get the order number from the query parameter
const orderNumber = new URLSearchParams(window.location.search).get('number');
const order = await fetch(`/actions/commerce/stripe-express-checkout/get-order?number=${orderNumber}`);
```

## Configuration

Configuration can be done globally in the plugin settings or locally by passing settings to
the `craft.expressCheckout.buttons` function. Passing the settings to the function will override the global settings.
You can find all the options in the [Stripe documentation](https://docs.stripe.com/js/element/express_checkout_element).

| Setting Name              | Description                                                                                                                                              |
|---------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `items`                   | List of item object with and `id` and `qty`.                                                                                                             |
| `itemId`                  | ID of the variant to sell.                                                                                                                               |
| `gatewayId`               | Select the gateway through which payments will be processed.                                                                                             |
| `shippingAddressRequired` | Checking this will prompt the user for their shipping address.                                                                                           |
| `phoneNumberRequired`     | Checking this box will prompt the user for their phone number.                                                                                           |
| `restrictCountries`       | Checking this will restrict the Stripe 'allowedCountries' to your country list in Commerce -> Store Settings -> Store -> Country List.                   |
| `successUrl`              | The URL that Stripe redirects to after a successful transaction. The 'successUrl' gets a query parameter called 'number' that contains the order number. |
| `loaderTemplate`          | The path to the loader template.                                                                                                                         |
| `buttonHeight`            | The height of the buttons.                                                                                                                               |
| `applePayTheme`           | The theme of the Apple Pay button.                                                                                                                       |
| `googlePayTheme`          | The theme of the GooglePay button.                                                                                                                       |
| `paypalTheme`             | The theme of the PayPal button.                                                                                                                          |
| `applePayType`            | The type of the Apple Pay button.                                                                                                                        |
| `googlePayType`           | The type of the GooglePay button.                                                                                                                        |
| `paypalType`              | The type of the PayPal button.                                                                                                                           |
| `maxColumns`              | The maximum number of columns.                                                                                                                           |
| `maxRows`                 | The maximum number of rows.                                                                                                                              |
| `overflow`                | Choose how the overflow should be handled.                                                                                                               |
| `paymentMethodOrder`      | The order of the payment methods.                                                                                                                        |
| `showApplePay`            | Show the Apple Pay button.                                                                                                                               |
| `showGooglePay`           | Show the GooglePay button.                                                                                                                               |
| `phoneField`              | Map the phone number to plain text on the order field layout. This will only take effect if you enable 'Phone Number Required'.                          |

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

## Extending and Customizing

### Event Hooks

| Class                | Event                                             |
|----------------------|---------------------------------------------------|
| StripeController     | `EVENT_BEFORE_UPDATE_SHIPPING_RATE`               |
| StripeController     | `EVENT_AFTER_UPDATE_SHIPPING_RAT`E                |
| StripeController     | `EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_BEFORE_SAVE` |
| StripeController     | `EVENT_UPDATE_SHIPPING_ADDRESS_ORDER_AFTER_SAVE`  |
| ProcessStripeWebhook | `EVENT_MODIFY_ORDER_DETAILS`                      |
| ProcessStripeWebhook | `EVENT_BEFORE_SAVE_SHIPPING_ADDRESS`              |
| ProcessStripeWebhook | `EVENT_AFTER_SAVE_SHIPPING_ADDRESS`               |
| ProcessStripeWebhook | `EVENT_BEFORE_SAVE_BILLING_ADDRESS`               |
| ProcessStripeWebhook | `EVENT_AFTER_SAVE_BILLING_ADDRESS`                |
| ProcessStripeWebhook | `EVENT_BEFORE_SET_ORDER_CUSTOMER`                 |
| ProcessStripeWebhook | `EVENT_WEBHOOK_FAILED`                            |
| ProcessStripeWebhook | `EVENT_RECEIVED_WEBHOOK`                          |

### Frontend JS

Each button uses the `StripeExpressCheckout` class. This class is responsible for creating the Stripe Element and
handling the payment intent.
The instance gets added to the `window.stripeExpressCheckouts` object. You can give your express checkout buttons a
custom name by passing the `name` option to the `craft.expressCheckout.buttons` function.
You can then access the instance by calling `window.stripeExpressCheckouts['your-custom-name']`
or `StripeExpressCheckout.getByName('your-custom-name')`.

Take a look at
the [`StripeExpressCheckout`](https://github.com/craftunit/stripe-express-checkout/blob/main/src/web/assets/expresscheckout/js/main.js)
class to see all the available events and methods.

#### Events

| Event                           | Description                                                    |
|---------------------------------|----------------------------------------------------------------|
| stripe-express-checkout:init    | Called after instance was created and added to `window` object |
| stripe-express-checkout:success | Called after successful `onConfirm`                            |
| stripe-express-checkout:error   | Called after failure in `onConfirm`                            |

All Events get dispatched on the window object. You can listen to them by adding an event listener to the window object.

```javascript
window.addEventListener('stripe-express-checkout:init', event => {
  const {name, instance} = event.detail;
  console.log(name, instance);
});
```

#### Public Methods

| Method Name  | Parameters              | Description                  |
|--------------|-------------------------|------------------------------|
| `setItemQty` | id: number, qty: number | Set the quantity of an item. |

#### Example

Below is a example showing how to manipulate the quantity of a product in the cart using the `setItemQty` method.

  ```twig
  <div class="quantity">
    <input type="number" name="qty" value="1" min="1">
  </div>

  {% set params = requiredParams | merge(optionalParams) %}
  {{ craft.expressCheckout.buttons({
    name: 'foo',
    items: [{
      id: variant.id,
      qty: 1,
    }]
  }) | raw }}

  <script>
    // Wait for the express checkout to initialize
    window.addEventListener('stripe-express-checkout:init', event => {
      // Get the instance by name
      const {name, instance} = event.detail;
      if (name !== 'product-express-checkout') return;

      const quantityInput = document.querySelector('.quantity input');
      quantityInput.addEventListener('input', event => {
        const qty = parseInt(event.target.value, 10);
        // Set the quantity of the item
        instance.setItemQty({{ variant.id }}, qty);
      });
    });
  </script>
```
