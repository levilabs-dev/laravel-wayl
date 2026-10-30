<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Contracts;

use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Data\Links\LinkBatch;
use LeviLabs\LaravelWayl\Data\Links\LinkData;
use LeviLabs\LaravelWayl\Data\Links\LinkPage;
use LeviLabs\LaravelWayl\Enums\LinkEnvironment;
use LeviLabs\LaravelWayl\Enums\LinkStatus;

interface WaylLinkServiceContract
{
    /**
     * Creates a payment link and returns the checkout URL to send to the buyer.
     *
     * referenceId must be unique across every link on the merchant. total is
     * whole IQD and at least 1000. When line items are sent, they must add
     * up to total. webhookUrl, webhookSecret, and redirectionUrl fall back
     * to config. The secret is stored encrypted on wayl_links because Wayl
     * does not return it later.
     *
     * @param  array<int, LineItem>|null  $lineItems
     */
    public function create(
        string $referenceId,
        int $total,
        ?array $lineItems = null,
        ?string $customParameter = null,
        ?LinkEnvironment $environment = null,
        ?string $currency = null,
        ?string $webhookUrl = null,
        ?string $webhookSecret = null,
        ?string $redirectionUrl = null,
        ?string $linkExpiresIn = null,
        ?string $merchant = null,
    ): LinkData;

    /**
     * The authoritative source of truth for a link. Always call this before
     * fulfilling an order — a webhook and the browser redirect are hints.
     */
    public function get(string $referenceId, ?string $merchant = null): LinkData;

    /**
     * Newest links first. statuses filters the result; take and skip paginate
     * (Wayl defaults to 10 and 0).
     *
     * @param  array<int, LinkStatus>|null  $statuses
     */
    public function list(?int $take = null, ?int $skip = null, ?array $statuses = null, ?string $merchant = null): LinkPage;

    /**
     * Loads 1–100 links by the reference IDs you issued. IDs that do not
     * belong to this merchant are left out of the result.
     *
     * @param  array<int, string>  $referenceIds
     */
    public function batch(array $referenceIds, ?string $merchant = null): LinkBatch;

    /**
     * Cancels an unpaid link. Buyers can no longer pay it.
     */
    public function invalidate(string $referenceId, ?string $merchant = null): LinkData;

    /**
     * Invalidates the link when it is still pending. Wayl returns success
     * without changing a link that has already moved on.
     */
    public function invalidateIfPending(string $referenceId, ?string $merchant = null): LinkData;

    /**
     * Confirms the merchant API key with GET /api/v1/verify-auth-key.
     * Returns Wayl's message. Throws WaylException when the key is rejected.
     */
    public function verify(?string $merchant = null): string;

    /**
     * Checks the HMAC-SHA256 signature of a webhook against the raw body.
     *
     * $secret defaults to WAYL_WEBHOOK_SECRET. Pass the per-link secret when
     * create() did not use the configured one. Never parse the body first.
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, ?string $secret = null): bool;
}
