# Release Notes for Stripe Express Checkout



## 1.0.5 - 2026-05-29
### Added
- The express checkout request now flags itself via `Craft::$app->params['stripeExpressCheckout']` before processing payment. Host projects can read this flag (e.g. in an `EVENT_BUILD_GATEWAY_REQUEST` handler) to distinguish the express checkout flow from the regular onsite checkout, since both run through `PaymentIntents::createPaymentIntent`.

## 1.0.4 - 2026-05-20
### Fixed
- README: corrected `setItemQty` parameter order to `(qty, id?)` to match the actual JS signature. Previously documented as `(id, qty)`.

## 1.0.3 - 2026-05-20
### Fixed
- Order is now created with the correct `orderSiteId` matching the site the customer placed the order from. Previously fell back to the first site in the
  store.

### Added
- `siteId` is sent with every plugin API request so the order site is deterministic regardless of the AJAX site-resolution.

## 1.0.0
- Initial release
