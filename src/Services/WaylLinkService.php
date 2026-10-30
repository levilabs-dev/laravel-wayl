<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Services;

use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Data\Links\LinkBatch;
use LeviLabs\LaravelWayl\Data\Links\LinkData;
use LeviLabs\LaravelWayl\Data\Links\LinkPage;
use LeviLabs\LaravelWayl\Enums\LineItemType;
use LeviLabs\LaravelWayl\Enums\LinkEnvironment;
use LeviLabs\LaravelWayl\Enums\LinkStatus;
use LeviLabs\LaravelWayl\Events\Links\LinkCreated;
use LeviLabs\LaravelWayl\Events\Links\LinkStatusChecked;
use LeviLabs\LaravelWayl\Exceptions\WaylException;
use LeviLabs\LaravelWayl\Services\Concerns\TalksToWayl;
use LeviLabs\LaravelWayl\Support\LinkExpiry;
use LeviLabs\LaravelWayl\Support\WebhookVerifier;

final class WaylLinkService implements WaylLinkServiceContract
{
    use TalksToWayl;

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
    ): LinkData {
        $this->assertReferenceId($referenceId);
        $this->assertMinimumAmount($total, 'link total');

        $currency = strtoupper($currency ?? $this->nullableString(config('wayl.currency')) ?? 'IQD');
        $this->assertCurrency($currency);

        if ($lineItems === []) {
            $lineItems = null;
        }

        if ($lineItems !== null) {
            $this->assertLineItemsReconcile($total, $lineItems);
        }

        $merchant ??= $this->defaultMerchant();
        $environment ??= $this->environment();
        $webhookUrl ??= $this->nullableString(config('wayl.webhook_url'));
        $webhookSecret ??= $this->secret(config('wayl.webhook_secret'));
        $redirectionUrl ??= $this->nullableString(config('wayl.redirection_url'));
        $linkExpiresIn ??= $this->nullableString(config('wayl.link_expires_in'));

        if ($webhookUrl !== null) {
            $this->assertUrl($webhookUrl, 'webhookUrl');
        }

        if ($redirectionUrl !== null) {
            $this->assertUrl($redirectionUrl, 'redirectionUrl');
        }

        if ($webhookSecret !== null) {
            $this->assertWebhookSecret($webhookSecret);
        }

        if ($linkExpiresIn !== null) {
            LinkExpiry::assertValid($linkExpiresIn);
        }

        $envelope = $this->send($merchant, 'link creation', 'post', '/api/v1/links', array_filter([
            'env' => $environment->value,
            'referenceId' => $referenceId,
            'total' => $total,
            'currency' => $currency,
            'customParameter' => $customParameter,
            'lineItem' => $lineItems === null ? null : array_map(
                fn (LineItem $item): array => $item->toApiArray(),
                $lineItems,
            ),
            'webhookUrl' => $webhookUrl,
            'webhookSecret' => $webhookSecret,
            'redirectionUrl' => $redirectionUrl,
            'linkExpiresIn' => $linkExpiresIn,
        ], fn (mixed $value): bool => $value !== null));

        $link = LinkData::fromArray($this->resource($envelope, 'link creation'));

        LinkCreated::dispatch($link, $merchant, $environment, $webhookSecret);

        return $link;
    }

    public function get(string $referenceId, ?string $merchant = null): LinkData
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'link retrieval',
            'get',
            '/api/v1/links/'.$this->encode($referenceId),
            idempotent: true,
        );

        return $this->checked($envelope, 'link retrieval', $merchant);
    }

    public function list(?int $take = null, ?int $skip = null, ?array $statuses = null, ?string $merchant = null): LinkPage
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'link list',
            'get',
            $this->withQuery('/api/v1/links', [
                'take' => $take,
                'skip' => $skip,
                'statuses' => $this->linkStatusValues($statuses),
            ]),
            idempotent: true,
        );

        $links = LinkData::collection($this->collection($envelope, 'link list'));

        foreach ($links as $link) {
            LinkStatusChecked::dispatch($link, $merchant);
        }

        return new LinkPage($links, $this->message($envelope));
    }

    public function batch(array $referenceIds, ?string $merchant = null): LinkBatch
    {
        $count = count($referenceIds);

        if ($count < 1 || $count > 100) {
            throw WaylException::invalidBatchSize($count);
        }

        foreach ($referenceIds as $referenceId) {
            $this->assertReferenceId($referenceId);
        }

        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send($merchant, 'link batch retrieval', 'post', '/api/v1/links/batch', [
            'referenceIds' => array_values($referenceIds),
        ]);

        $links = LinkData::collection($this->collection($envelope, 'link batch retrieval'));

        foreach ($links as $link) {
            LinkStatusChecked::dispatch($link, $merchant);
        }

        $totalRequested = $envelope['totalRequested'] ?? $count;
        $totalFound = $envelope['totalFound'] ?? count($links);

        return new LinkBatch(
            links: $links,
            message: $this->message($envelope),
            totalRequested: is_numeric($totalRequested) ? (int) $totalRequested : $count,
            totalFound: is_numeric($totalFound) ? (int) $totalFound : count($links),
        );
    }

    public function invalidate(string $referenceId, ?string $merchant = null): LinkData
    {
        return $this->postInvalidate($referenceId, '/invalidate', 'link invalidation', $merchant);
    }

    public function invalidateIfPending(string $referenceId, ?string $merchant = null): LinkData
    {
        return $this->postInvalidate($referenceId, '/invalidate-if-pending', 'pending link invalidation', $merchant);
    }

    public function verify(?string $merchant = null): string
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send($merchant, 'authentication check', 'get', '/api/v1/verify-auth-key', idempotent: true);

        return $this->message($envelope);
    }

    public function verifyWebhookSignature(string $rawBody, string $signature, ?string $secret = null): bool
    {
        $secret ??= $this->secret(config('wayl.webhook_secret'));

        if ($secret === null) {
            throw WaylException::missingWebhookSecret();
        }

        return WebhookVerifier::verify($rawBody, $signature, $secret);
    }

    private function postInvalidate(string $referenceId, string $suffix, string $action, ?string $merchant): LinkData
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            $action,
            'post',
            '/api/v1/links/'.$this->encode($referenceId).$suffix,
        );

        return $this->checked($envelope, $action, $merchant);
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function checked(array $envelope, string $action, string $merchant): LinkData
    {
        $link = LinkData::fromArray($this->resource($envelope, $action));

        LinkStatusChecked::dispatch($link, $merchant);

        return $link;
    }

    /**
     * Wayl says the line items must equal the total, and each row is either
     * increase or decrease. The spec does not say whether decrease subtracts.
     * A total that matches either the signed sum or the gross sum is accepted;
     * anything else is rejected before a request is sent.
     *
     * @param  array<int, LineItem>  $lineItems
     */
    private function assertLineItemsReconcile(int $total, array $lineItems): void
    {
        $signed = 0;
        $gross = 0;

        foreach ($lineItems as $item) {
            $gross += $item->amount;
            $signed += $item->type === LineItemType::Increase ? $item->amount : -$item->amount;
        }

        if ($total !== $signed && $total !== $gross) {
            throw WaylException::lineItemsDoNotReconcile($total, $signed, $gross);
        }
    }

    /**
     * @param  array<int, LinkStatus>|null  $statuses
     * @return array<int, string>|null
     */
    private function linkStatusValues(?array $statuses): ?array
    {
        if ($statuses === null || $statuses === []) {
            return null;
        }

        return array_map(fn (LinkStatus $status): string => $status->value, $statuses);
    }

    private function secret(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
