<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Links;

use Carbon\CarbonImmutable;
use LeviLabs\LaravelWayl\Enums\LinkStatus;
use LeviLabs\LaravelWayl\Support\Amount;

/**
 * A Wayl payment link. `code` is only present on the create response —
 * keep it, later reads omit it. `reversable` keeps Wayl's spelling and is
 * only present when `type` is Order.
 *
 * Redirect URLs come back with `referenceId` and `orderid` already
 * appended. `orderid` is this object's `id`.
 */
final readonly class LinkData
{
    public function __construct(
        public string $referenceId,
        public string $id,
        public ?string $code,
        public int $total,
        public string $currency,
        public ?string $type,
        public ?string $paymentMethod,
        public ?bool $reversable,
        public LinkStatus $status,
        public ?CarbonImmutable $completedAt,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?string $url,
        public ?string $webhookUrl,
        public ?string $redirectionUrl,
        public ?string $linkExpiresIn,
        public ?string $customParameter,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            referenceId: (string) $data['referenceId'],
            id: (string) $data['id'],
            code: isset($data['code']) ? (string) $data['code'] : null,
            total: Amount::toInt($data['total'] ?? 0),
            currency: (string) ($data['currency'] ?? 'IQD'),
            type: isset($data['type']) ? (string) $data['type'] : null,
            paymentMethod: isset($data['paymentMethod']) ? (string) $data['paymentMethod'] : null,
            reversable: array_key_exists('reversable', $data) && $data['reversable'] !== null ? (bool) $data['reversable'] : null,
            status: LinkStatus::from((string) $data['status']),
            completedAt: isset($data['completedAt']) ? CarbonImmutable::parse((string) $data['completedAt']) : null,
            createdAt: isset($data['createdAt']) ? CarbonImmutable::parse((string) $data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? CarbonImmutable::parse((string) $data['updatedAt']) : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            webhookUrl: isset($data['webhookUrl']) ? (string) $data['webhookUrl'] : null,
            redirectionUrl: isset($data['redirectionUrl']) ? (string) $data['redirectionUrl'] : null,
            linkExpiresIn: isset($data['linkExpiresIn']) ? (string) $data['linkExpiresIn'] : null,
            customParameter: array_key_exists('customParameter', $data) && $data['customParameter'] !== null ? (string) $data['customParameter'] : null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, self>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item): self => self::fromArray($item), $items);
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function isSuccessful(): bool
    {
        return $this->isPaid();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'referenceId' => $this->referenceId,
            'id' => $this->id,
            'code' => $this->code,
            'total' => $this->total,
            'currency' => $this->currency,
            'type' => $this->type,
            'paymentMethod' => $this->paymentMethod,
            'reversable' => $this->reversable,
            'status' => $this->status->value,
            'completedAt' => $this->completedAt?->toIso8601String(),
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
            'url' => $this->url,
            'webhookUrl' => $this->webhookUrl,
            'redirectionUrl' => $this->redirectionUrl,
            'linkExpiresIn' => $this->linkExpiresIn,
            'customParameter' => $this->customParameter,
        ];
    }
}
