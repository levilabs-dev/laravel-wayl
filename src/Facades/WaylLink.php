<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Data\Links\LinkBatch;
use LeviLabs\LaravelWayl\Data\Links\LinkData;
use LeviLabs\LaravelWayl\Data\Links\LinkPage;
use LeviLabs\LaravelWayl\Enums\LinkEnvironment;
use LeviLabs\LaravelWayl\Enums\LinkStatus;

/**
 * @method static LinkData create(string $referenceId, int $total, ?array<int, LineItem> $lineItems = null, ?string $customParameter = null, ?LinkEnvironment $environment = null, ?string $currency = null, ?string $webhookUrl = null, ?string $webhookSecret = null, ?string $redirectionUrl = null, ?string $linkExpiresIn = null, ?string $merchant = null)
 * @method static LinkData get(string $referenceId, ?string $merchant = null)
 * @method static LinkPage list(?int $take = null, ?int $skip = null, ?array<int, LinkStatus> $statuses = null, ?string $merchant = null)
 * @method static LinkBatch batch(array<int, string> $referenceIds, ?string $merchant = null)
 * @method static LinkData invalidate(string $referenceId, ?string $merchant = null)
 * @method static LinkData invalidateIfPending(string $referenceId, ?string $merchant = null)
 * @method static string verify(?string $merchant = null)
 * @method static bool verifyWebhookSignature(string $rawBody, string $signature, ?string $secret = null)
 *
 * @see WaylLinkServiceContract
 */
class WaylLink extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WaylLinkServiceContract::class;
    }
}
