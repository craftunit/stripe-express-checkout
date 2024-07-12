class StripeExpressCheckout {
  orderNumber = null;

  constructor(stripeExpressCheckout, options) {
    this.options = options;
    this.name = options.name;
    this.container = stripeExpressCheckout;
    this.loadingStripe = this.container.querySelector('.stripe-express-loading');
    this.error = this.container.querySelector('.stripe-express-error');
    this.expressElement = this.container.querySelector('.stripe-express-element');
    this.id = options.id;
    this.publishableKey = options.stripeApiKey;
    this.items = options.items;
    // this.orderNumber = this.options.orderNumber;
    this.stripe = Stripe(this.publishableKey);
    this.#init();
  }

  #init() {
    this.elements = this.stripe.elements(this.#elementOptions);
    this.expressCheckoutElement = this.elements.create('expressCheckout', this.#expressCheckoutOptions);
    this.expressCheckoutElement.mount(this.expressElement);
    this.expressCheckoutElement.on('confirm', this.#onConfirm);
    this.expressCheckoutElement.on('ready', this.#onReady);
    this.expressCheckoutElement.on('click', this.#onClick);
    this.expressCheckoutElement.on('shippingaddresschange', this.#onShippingAddressChanged);
    this.expressCheckoutElement.on('shippingratechange', this.#onShippingRateChange);
    this.expressCheckoutElement.on('cancel', this.#onCancel);
  }

  static init(id, options = {}) {
    const stripeExpressCheckout = document.querySelector(`.stripe-express[data-express-id="${id}"]`);
    if (stripeExpressCheckout) {
      const instance = new StripeExpressCheckout(stripeExpressCheckout, options);
      const name = options.name || id;
      if (window.stripeExpressCheckouts) {
        window.stripeExpressCheckouts[name] = instance;
      } else {
        window.stripeExpressCheckouts = {
          [name]: instance,
        };
      }
      // emit event
      window.dispatchEvent(new CustomEvent('stripe-express-checkout:init', {
        detail: {
          name,
          instance,
        },
      }));
    }
  }

  static getByName(name) {
    if (!window.stripeExpressCheckouts) return;

    if (!window.stripeExpressCheckouts[name]) return null;

    return window.stripeExpressCheckouts[name];
  }

  /* GETTERS */
  get #elementOptions() {
    return {
      mode: 'payment',
      currency: this.options.currency,
      amount: this.options.amount,
    }
  }

  get #expressCheckoutOptions() {
    return {
      buttonTheme: this.options.buttonTheme,
      buttonHeight: this.options.buttonHeight,
      buttonType: this.options.buttonType,
      layout: this.options.layout,
      paymentMethodOrder: this.options.paymentMethodOrder,
      paymentMethods: this.options.wallets,
    };
  }

  get #createIntentOptions() {
    return {
      [window.csrfTokenName]: window.csrfTokenValue,
      items: this.options.items,
      currency: this.options.currency,
      orderNumber: this.orderNumber,
    };
  }

  get #onClickResolveOptions() {
    return {
      // TODO: Do we always need to request email? probably yes
      emailRequired: true,
      allowedShippingCountries: this.options.allowedCountries || [],
      shippingAddressRequired: this.options.shippingAddressRequired || false,
      // shippingAddressRequired: false,
      // shippingRates: this.options.shippingRates || [],
      phoneNumberRequired: this.options.phoneNumberRequired || false,
      business: {
        name: this.options.businessName || '',
      },
      lineItems: this.options.lineItems,
      applePay: this.options.applePay || {},
      ...(this.options.shippingAddressRequired && this.options.shippingRates && { shippingRates: this.options.shippingRates })
    };
  }

  setItemQty = (id, qty) => {
    this.options.items = this.options.items.map(item => {
      if (item.id === id) {
        return {
          ...item,
          qty,
        };
      }
      return item;
    });
  }

  #onShippingAddressChanged = async event => {
    const { address } = event;
    const res = await fetch('/actions/stripe-express-checkout/stripe/update-shipping-address', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        [window.csrfTokenName]: window.csrfTokenValue,
        address,
        items: this.options.items,
        orderNumber: this.orderNumber,
      })
    });

    // TODO: Test applePay; See documentation: https://docs.stripe.com/js/elements_object/express_checkout_element_shippingaddresschange_event
    const { lineItems, orderNumber, total, shippingRates, applePay } = await res.json();
    if (orderNumber) {
      this.orderNumber = orderNumber;
    }

    this.elements.update({ amount: total.amount });
    event.resolve({ lineItems, shippingRates, applePay });
    this.options = {
      ...this.options,
      lineItems,
      shippingRates,
      applePay,
    };

    console.log("SHIPPINGADDRESSCHANGE END:", event)
  }

  #onShippingRateChange = async event => {
    console.log("SHIPPINGRATECHANGE START:", event);
    // TODO: Update amount based on shipping rate; use fetch to get new amount and update order
    const { shippingRate } = event;
    const res = await fetch('/actions/stripe-express-checkout/stripe/update-shipping-rate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        [window.csrfTokenName]: window.csrfTokenValue,
        shippingRateHandle: shippingRate.id,
        items: this.options.items,
        orderNumber: this.orderNumber,
      })
    });

    const { lineItems, total, orderNumber, shippingRates, applePay } = await res.json();
    if (orderNumber) {
      this.orderNumber = orderNumber;
    }

    this.elements.update({ amount: total.amount });

    console.log("SHIPPINGRATECHANGE END:", event)

    // TODO: on initial load event.resolve is undefined?
    event.resolve({
      lineItems,
      shippingRates,
      applePay,
    });
    this.options = {
      ...this.options,
      lineItems,
      shippingRates,
      applePay,
    };
  }

  /* HANDLE EVENTS */
  #onClick = event => {
    event.resolve(this.#onClickResolveOptions);
  }

  #onReady = () => this.loadingStripe.remove();

  #onCancel = async event => {
    // Do not delete a cart on cancel; user might want to try again
    if (this.options.cart) {
      // reset cart addresses; this is needed to make sure the cart is valid after cancel
      const res = await fetch('/actions/stripe-express-checkout/stripe/reset-addresses', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          [window.csrfTokenName]: window.csrfTokenValue,
          orderNumber: this.orderNumber,
        })
      });
      return;
    }

    if (!this.orderNumber) return;
    const res = await fetch('/actions/stripe-express-checkout/stripe/cancel', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        [window.csrfTokenName]: window.csrfTokenValue,
        orderNumber: this.orderNumber,
      })
    });
    const jsonResponse = await res.json();
    this.orderNumber = null;
  }

  #onPaymentFailed = event => {
    window.dispatchEvent(new CustomEvent('stripe-express-checkout:error', {
      detail: {
        name: this.name,
        orderNumber: this.orderNumber,
        error: event,
      },
    }));
  }

  #onConfirm = async (event) => {
    event.paymentFailed = this.#onPaymentFailed;
    const {error: submitError} = await this.elements.submit();
    if (submitError) {
      this.error.textContent = submitError.message;
      return;
    }

    const res = await fetch('/actions/stripe-express-checkout/stripe/create-intent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
      },
      body: JSON.stringify(this.#createIntentOptions)
    });
    const {
      client_secret: clientSecret,
      number,
    } = await res.json();
    const {error} = await this.stripe.confirmPayment({
      elements: this.elements,
      clientSecret,
      confirmParams: {
        return_url: `https://${window.location.hostname}${this.options.successUrl}?number=${number}`,
      },
    });

    if (error) {
      // dispatch error event hooking in on error
      event.paymentFailed(error);
    } else {
      // dispatch success event hooking in on success
      window.dispatchEvent(new CustomEvent('stripe-express-checkout:success', {
        detail: {
          name: this.name,
          number,
        },
      }));
    }
  };
}
