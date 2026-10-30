# Changelog

All notable changes to `levilabs/laravel-wayl` are documented here.

Project: **[Levi Labs](https://levilabs.dev)** · maintained by **Nizam Omer** ([nizaamomer.com](https://nizaamomer.com))

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Work on the default branch (`main`). **No `v1.0.0` tag has been published yet** — the first GitHub release / Packagist version will be **1.0.0** when you tag it.

Until then, install from source with:

`composer require levilabs/laravel-wayl:dev-main`

### Added

- `WaylLink::create()`, `get()`, `list()`, `batch()`, `invalidate()`, `invalidateIfPending()`, `verify()`, and `verifyWebhookSignature()`.
- `WaylRefund::create()`, `get()`, `list()`, and `cancel()`.
- `WaylProduct::list()` and `get()` for the catalog read endpoints.
- Typed DTOs for every documented response shape: `LinkData`, `LinkPage`, `LinkBatch`, `LineItem`, `WebhookPayload`, `RefundData`, `RefundPage`, `ProductData`, `ProductPage`.
- `LinkStatus`, `LinkEnvironment`, `LineItemType`, `RefundStatus`, and `RefundMethod` enums matching Wayl's published names, with `isTerminal()` / `isPaid()` / `isSuccessful()` helpers.
- `WebhookVerifier` — HMAC-SHA256 over the raw webhook body, compared to the bare hex digest in `x-wayl-signature-256` (and `x-wayl-signature`).
- Multi-merchant support via the `merchants` array in `config/wayl.php`.
- Automatic persistence: link and refund calls fire events that upsert `wayl_links` and `wayl_refunds`. The per-link webhook secret is stored encrypted. `code` and `environment` are kept from the create response, because later reads omit them.
- `payable` polymorphic relation on `WaylLink`.
- `php artisan wayl:sync-statuses` — re-checks open links and refunds still in `Requested`.
- `toArray()` on every response DTO.
- `WaylException::$statusCode` and `$errors` for HTTP failures, including Wayl's validation `errors` array.

### Security

- Webhook signatures are verified against the raw body. Parsed JSON is never re-encoded for the digest.
- POST and DELETE requests are not retried, so a timeout cannot create a second link or a second refund.
- TLS certificate verification cannot be disabled through configuration.
