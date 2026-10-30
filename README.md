# Laravel Wayl SDK — Payment Links, Refunds, Products & HMAC Webhooks

<p>
<a href="https://packagist.org/packages/levilabs/laravel-wayl"><img src="https://img.shields.io/packagist/v/levilabs/laravel-wayl.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-wayl/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-wayl/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-wayl"><img src="https://img.shields.io/packagist/dt/levilabs/laravel-wayl.svg?style=flat-square&label=Downloads&color=blue" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/levilabs/laravel-wayl"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-wayl.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-wayl.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for [Wayl](https://wayl.io) (Iraq) — checkout links, status checks, invalidation, refunds, product catalog reads, and HMAC-verified webhooks. Typed DTOs and enums for every operation in the published [OpenAPI v1](https://api.thewayl.com/openapi.v1.json) document, multi-merchant support, and automatic persistence.

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)) — built by [Nizam Omer](https://nizaamomer.com)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Checking your API key](#checking-your-api-key)
- [Creating a payment link](#creating-a-payment-link)
- [Line items](#line-items)
- [The webhook — verify the raw body](#the-webhook--verify-the-raw-body)
- [The return URL](#the-return-url)
- [Reading links](#reading-links)
- [Invalidating a link](#invalidating-a-link)
- [Missed webhooks: the sync command](#missed-webhooks-the-sync-command)
- [Refunds](#refunds)
- [Products](#products)
- [Multiple merchants](#multiple-merchants)
- [Automatic persistence](#automatic-persistence)
- [Error handling](#error-handling)
- [Full example](#full-example)
- [Security](#security)
- [Testing](#testing)
- [Wayl API reference](#wayl-api-reference)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Author](#author)
- [License](#license)

## Requirements

- PHP 8.2+ (Laravel **13.x** requires PHP **8.3+** on the application)
- Laravel 11.x, 12.x, or 13.x

## Installation

Stable release (after `v1.0.0` is tagged on GitHub / Packagist):

```bash
composer require levilabs/laravel-wayl
```

While the first release is pending, use the `main` branch:

```bash
composer require levilabs/laravel-wayl:dev-main
```

Publish the config file:

```bash
php artisan vendor:publish --tag="wayl-config"
```

Run the migrations — creates `wayl_links` and `wayl_refunds`. Every link and refund call is persisted automatically:

```bash
php artisan migrate
```

Add your Wayl merchant token to `.env` (from the [Wayl dashboard](https://dashboard.wayl.io)):

```env
WAYL_API_KEY=your-merchant-token
WAYL_ENVIRONMENT=test                         # "test" or "live" — sent as the link env field
WAYL_CURRENCY=IQD
WAYL_WEBHOOK_URL=https://your-app.test/wayl/webhook
WAYL_WEBHOOK_SECRET=replace-with-10-to-255-characters
WAYL_REDIRECTION_URL=https://your-app.test/checkout/return
WAYL_LINK_EXPIRES_IN=1h                       # 30m, 24h, 7d — between 1m and 30d
```

Test and live links use the same host (`https://api.thewayl.com`) and the same API key. `WAYL_ENVIRONMENT` chooses the `env` field on link creation. Set `WAYL_BASE_URL` only when Wayl has given you a different host, such as `https://api.thewayl-staging.com`.

Amounts are whole Iraqi dinars. A link total and a refund amount must each be at least **1000**.

## Checking your API key

```php
use LeviLabs\LaravelWayl\Facades\WaylLink;

WaylLink::verify(); // Wayl's confirmation message, or a WaylException
```

This calls `GET /api/v1/verify-auth-key`. Use it when a merchant pastes a token into your admin, before you save it.

## Creating a payment link

`referenceId` is your id. It must be unique across every link that merchant has ever created. The response `url` is the checkout page — send the buyer there. `code` is the short code inside that URL, and Wayl only returns it from this call, so the SDK stores it.

```php
use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Enums\LineItemType;
use LeviLabs\LaravelWayl\Facades\WaylLink;

$link = WaylLink::create(
    referenceId: 'order-42',
    total: 10000,
    lineItems: [
        new LineItem('Basket', 12000, LineItemType::Increase),
        new LineItem('Discount', 2000, LineItemType::Decrease),
    ],
    customParameter: (string) $order->id,
);

return redirect($link->url);
```

`currency`, `webhookUrl`, `webhookSecret`, `redirectionUrl`, and `linkExpiresIn` fall back to config. Pass `environment: LinkEnvironment::Live` when this one link should take real money and the app default is still `test`.

Wayl does not echo `env`, `webhookSecret`, or `lineItem` on later reads. This SDK stores the environment and an encrypted copy of the secret on `wayl_links` at creation time.

## Line items

The request field is `lineItem` (singular). Each label is 3–255 characters and each amount is at least 1 IQD. When you send rows, they have to add up to `total`.

Wayl's spec says the rows must equal the total, and each row is `increase` or `decrease`. It does not say whether a decrease row subtracts. A total that matches either reading is accepted:

- signed: increases minus decreases (`12000 - 2000 = 10000`)
- gross: every row added together

Anything else is rejected locally, before a request is sent.

## The webhook — verify the raw body

Wayl signs the **raw body bytes** with HMAC-SHA256 and the `webhookSecret` from `create()`. The digest is bare hex (64 characters, no `sha256=` prefix) in `x-wayl-signature-256`. Some Wayl plugins send the same digest as `x-wayl-signature`. The body arrives as `Content-Type: text/plain`, so `$request->all()` can be empty — use `getContent()`.

The webhook's `paymentStatus` value `Paid` is a different vocabulary from link status. Fulfil when `WaylLink::get()` reports `Complete` or `Delivered`. There is no timestamp in the signature, and Wayl retries on timeout, so deduplicate on the payload `id`.

```php
use Illuminate\Support\Facades\Cache;
use LeviLabs\LaravelWayl\Data\Links\WebhookPayload;
use LeviLabs\LaravelWayl\Facades\WaylLink;

Route::post('/wayl/webhook', function (\Illuminate\Http\Request $request) {
    $raw = $request->getContent();
    $signature = $request->header('x-wayl-signature-256', $request->header('x-wayl-signature'));

    if (! is_string($signature) || ! WaylLink::verifyWebhookSignature($raw, $signature)) {
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    $payload = WebhookPayload::fromRawBody($raw);

    if ($payload->id !== null && ! Cache::add('wayl:webhook:'.$payload->id, true, now()->addDays(7))) {
        return response()->noContent();
    }

    if ($payload->referenceId === null) {
        return response()->noContent();
    }

    $link = WaylLink::get($payload->referenceId);

    if ($link->isPaid()) {
        // fulfil the order
    }

    return response()->noContent();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
```

Pass `$secret` as the third argument of `verifyWebhookSignature()` when that link was created with a secret other than `WAYL_WEBHOOK_SECRET`. The encrypted value is on the `WaylLink` model (`webhook_secret`) if you need to look it up by `reference_id`.

Return HTTP 200 after you have recorded a genuine delivery so Wayl stops retrying. A bad signature should get 400.

## The return URL

Wayl redirects the browser to `redirectionUrl` and appends `referenceId` and `orderid`. `orderid` is the link's `id`. Load the link before you fulfil — the redirect fires for more than the happy path, and the webhook may still be in flight:

```php
$link = WaylLink::get($request->string('referenceId')->toString());

if ($link->isPaid()) {
    // fulfil
}
```

`$link->status` is one of `Created`, `Pending`, `Processing`, `Complete`, `Delivered`, `Cancelled`, `Rejected`, `Returned`. Wayl publishes the names without a transition graph. This SDK treats `Complete` and `Delivered` as paid (`isPaid()` / `isSuccessful()`), and those two plus `Cancelled`, `Rejected`, and `Returned` as finished (`isTerminal()`), so the sync command stops polling them.

## Reading links

```php
$link = WaylLink::get('order-42');

$link->status->isTerminal();
$link->isPaid();
$link->total;             // int, whole IQD
$link->paymentMethod;     // null until the buyer pays
$link->url;

$page = WaylLink::list(take: 10, skip: 0, statuses: [LinkStatus::Pending, LinkStatus::Complete]);

$batch = WaylLink::batch(['order-42', 'order-43']); // 1–100 ids
$batch->totalRequested;
$batch->totalFound;       // ids that do not belong to this merchant are omitted
```

`list()` asks Wayl for exploded query parameters (`statuses=Pending&statuses=Complete`).

## Invalidating a link

`invalidate()` cancels an unpaid link. Buyers can no longer pay it. `invalidateIfPending()` does the same only while the link is still pending — Wayl returns success and leaves the link as it is once payment has moved on.

```php
$link = WaylLink::invalidateIfPending('order-42');
```

Use a refund once the link is paid.

## Missed webhooks: the sync command

If `webhookUrl` was down, the signature check failed, or Wayl dropped the delivery, an open link stays open until something asks again. Run:

```bash
php artisan wayl:sync-statuses
```

This re-fetches links still in `Created`, `Pending`, or `Processing`, and refunds still in `Requested`. `--limit` caps each of those two queries (default 100). Schedule it in `bootstrap/app.php`:

```php
->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
    $schedule->command('wayl:sync-statuses')->everyFiveMinutes();
})
```

## Refunds

A refund needs a paid order, a reason of 100–1500 characters, and an amount of at least 1000 IQD that does not exceed what the gateway collected. This moves real money — put your own approval step in front of it.

```php
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Facades\WaylRefund;

$refund = WaylRefund::create(
    referenceId: 'order-42',
    reason: str_repeat('The buyer asked for a refund because the item never shipped. ', 3),
    amount: 10000,
);

$refund->isSuccessful();   // status is Refunded
$refund->status;           // Requested | Refunded | Rejected | Cancelled
$refund->id;

WaylRefund::get($refund->id);
WaylRefund::list(referenceId: 'order-42', statuses: [RefundStatus::Requested]);
WaylRefund::cancel($refund->id); // only while status is Requested
```

Omit `refundMethod` and `accountNumber` to refund the original payment method. Pass both to send the money to QiCard, FIB, or ZainCash instead:

```php
WaylRefund::create(
    referenceId: 'order-42',
    reason: str_repeat('Refund to the buyer FIB account they sent us. ', 3),
    amount: 10000,
    refundMethod: RefundMethod::Fib,
    accountNumber: 'ACC-9',
);
```

`GET /refunds/{id}` and the cancel response omit `id`. The SDK fills it from the path you asked for.

## Products

Read-only catalog access. The list is Digital, Physical, and Service products (subscription products are excluded by Wayl) and comes back newest first.

```php
use LeviLabs\LaravelWayl\Facades\WaylProduct;

$page = WaylProduct::list(take: 10, skip: 0);
$product = WaylProduct::get($page->products[0]->id);

$product->name;
$product->price;          // string, as Wayl returns it
$product->productType;
$product->quantity;       // Wayl's `qt`
$product->url;
```

Products are not written to your database. They are catalog data, not a payment you have to reconcile.

## Multiple merchants

Add more entries under `merchants` in `config/wayl.php`, each with its own API key, then pass the merchant name:

```php
WaylLink::create('order-42', 10000, merchant: 'second_store');
WaylLink::get('order-42', merchant: 'second_store');
```

## Automatic persistence

`create()`, `get()`, `list()`, `batch()`, `invalidate()`, and `invalidateIfPending()` fire `LinkCreated` or `LinkStatusChecked`, which upsert `wayl_links`. Refund calls fire `RefundCreated`, `RefundStatusChecked`, or `RefundCancelled`, which upsert `wayl_refunds` and attach them to the link when the reference id or Wayl link id matches.

A later `get()` does not wipe `code`, `environment`, or `webhook_secret`, because Wayl does not send those back.

Link your own models through the `payable` relation:

```php
use LeviLabs\LaravelWayl\Models\WaylLink;

WaylLink::query()->where('reference_id', 'order-42')->first()?->payable()->associate($order)->save();
```

`webhook_secret` is encrypted with your `APP_KEY` and hidden from model serialization. Reading the attribute decrypts it.

## Error handling

Wayl signals failure with the HTTP status and a `message`, plus an `errors` array on validation failures (HTTP 422). A body with `success: false` is also a failure. Both become a `WaylException`:

```php
use LeviLabs\LaravelWayl\Exceptions\WaylException;

try {
    WaylLink::create(referenceId: 'order-42', total: 10000);
} catch (WaylException $e) {
    $e->statusCode;   // 422
    $e->errors;       // Wayl's errors array, or null
    $e->getMessage(); // "Wayl link creation request failed (HTTP 422): ..."
}
```

Local checks throw `InvalidArgumentException` before any HTTP call: totals under 1000, a reference id outside its length limit, a bad `linkExpiresIn`, line items that do not add up, a refund reason outside 100–1500 characters, and a refund method sent without an account number (or the reverse). An unknown merchant or a missing API key throws `WaylMerchantException`.

## Full example

[`docs/examples/PaymentController.php`](docs/examples/PaymentController.php) is a commented controller covering credential check, link creation, the return URL, the webhook, invalidation, and refund. It is illustrative (not autoloaded) — copy what you need into your app.

## Security

- **TLS verification is never disabled.** This SDK does not expose a way to set Guzzle's `verify => false`.
- **Webhook signatures cover the raw body.** `verifyWebhookSignature()` hashes those exact bytes. Re-encoding JSON will not match.
- **POST and DELETE are not retried.** A timed-out create or refund cannot be sent a second time by the HTTP client. GET requests retry only when you raise `WAYL_HTTP_RETRY_TIMES` (that value is the maximum number of attempts; `1` means a single try).
- **Amounts are checked locally** against Wayl's 1000 IQD minimum, and refund destinations must be sent as a pair, before a request goes out.
- **The per-link webhook secret is encrypted at rest** on `wayl_links`. The API key stays in `.env`. Rotate `WAYL_API_KEY` and `WAYL_WEBHOOK_SECRET` if they are ever exposed.
- **Deduplicate webhook deliveries on `id`.** The signature has no timestamp, so a captured body stays valid.

If you discover a security issue, please see [SECURITY.md](SECURITY.md) instead of using the public issue tracker. Reports are handled by **Levi Labs** and **Nizam Omer**.

## Testing

```bash
composer test        # Pest
composer analyse      # Larastan / PHPStan (level 8)
composer format        # Laravel Pint
```

## Wayl API reference

This SDK follows the published [Wayl OpenAPI v1](https://api.thewayl.com/openapi.v1.json) document: authentication, links, refunds, and products. Interactive docs live at [api.thewayl.com/reference](https://api.thewayl.com/reference). Product pages: [wayl.io](https://wayl.io) and [wayl.io/docs](https://wayl.io/docs).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what's changed in each release.

## Contributing

Bug reports and feature ideas are welcome via [GitHub Issues](https://github.com/levilabs-dev/laravel-wayl/issues). For contribution guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md).

## Author

**[Levi Labs](https://levilabs.dev)** — software development studio · [GitHub](https://github.com/levilabs-dev) · [hello@levilabs.dev](mailto:hello@levilabs.dev)

Created and maintained by **Nizam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com)

## License

This package is open-source software licensed under the [MIT License](LICENSE.md).

Copyright (c) 2026 **Levi Labs** and **Nizam Omer**.
