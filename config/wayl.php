<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Wayl Merchant
    |--------------------------------------------------------------------------
    |
    | The merchant used when no explicit merchant is requested. Matches a key
    | in the "merchants" array below.
    |
    */
    'default' => env('WAYL_MERCHANT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Wayl Merchants
    |--------------------------------------------------------------------------
    |
    | Each merchant holds its own API key so a single application can accept
    | payments through multiple Wayl accounts. Every request is authenticated
    | with the X-WAYL-AUTHENTICATION header.
    |
    | The key is the merchant token from the Wayl dashboard. Test and live
    | payments share this key and this host — the mode is chosen per link
    | with the "env" field (see WAYL_ENVIRONMENT below), not by swapping hosts.
    |
    | base_url: leave the default unless Wayl has issued you a different host
    | (the OpenAPI spec also lists https://api.thewayl-staging.com).
    |
    */
    'merchants' => [
        'default' => [
            'api_key' => env('WAYL_API_KEY'),
            'base_url' => env('WAYL_BASE_URL', 'https://api.thewayl.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Link Environment & Currency
    |--------------------------------------------------------------------------
    |
    | environment: "test" or "live". Sent as the link's env field. A test
    | link does not move real money. This value is not returned by later
    | reads, so the SDK stores it on wayl_links at creation time.
    |
    | currency: Wayl currently only supports IQD. Amounts are whole dinars.
    | A link total and a refund amount must each be at least 1000.
    |
    */
    'environment' => env('WAYL_ENVIRONMENT', 'test'),
    'currency' => env('WAYL_CURRENCY', 'IQD'),

    /*
    |--------------------------------------------------------------------------
    | Webhook & Redirect URLs
    |--------------------------------------------------------------------------
    |
    | webhook_url: where Wayl POSTs a payment update. Read the raw body
    | before parsing it — Wayl signs those exact bytes and sends
    | Content-Type: text/plain, which can leave Laravel's parsed input empty.
    |
    | webhook_secret: HMAC secret (10–255 characters). Wayl stores it per
    | link and never echoes it back, so this SDK keeps an encrypted copy on
    | wayl_links when a link is created. Reuse one secret, or pass a
    | different secret to create() and verify with that value.
    |
    | redirection_url: where the buyer's browser returns after payment.
    | Wayl appends referenceId and orderid as query parameters. Treat that
    | redirect as a hint and confirm with WaylLink::get() before fulfilling.
    |
    | link_expires_in: how long the checkout URL stays payable. Format is a
    | number plus m, h, or d ("30m", "1h", "24h", "7d"), from 1m to 30d.
    | Wayl defaults to 1h when the field is omitted.
    |
    */
    'webhook_url' => env('WAYL_WEBHOOK_URL'),
    'webhook_secret' => env('WAYL_WEBHOOK_SECRET'),
    'redirection_url' => env('WAYL_REDIRECTION_URL'),
    'link_expires_in' => env('WAYL_LINK_EXPIRES_IN', '1h'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | TLS certificate verification is always enabled and cannot be disabled
    | through configuration. Retries apply only to idempotent GET requests.
    | retry_times is the maximum number of attempts (1 means a single try).
    |
    */
    'http' => [
        'timeout' => (int) env('WAYL_HTTP_TIMEOUT', 15),
        'retry_times' => (int) env('WAYL_HTTP_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('WAYL_HTTP_RETRY_SLEEP_MS', 200),
    ],

];
