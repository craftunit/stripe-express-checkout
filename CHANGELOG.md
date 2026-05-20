# Release Notes for Stripe Express Checkout



## 1.0.3 - 2026-05-20
### Fixed
- Order is now created with the correct `orderSiteId` matching the site the customer placed the order from. Previously fell back to the first site in the
  store.

### Added
- `siteId` is sent with every plugin API request so the order site is deterministic regardless of the AJAX site-resolution.

## 1.0.0
- Initial release
