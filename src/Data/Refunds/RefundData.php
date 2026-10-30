<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Refunds;

use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;
use LeviLabs\LaravelWayl\Support\Amount;

/**
 * A Wayl refund. GET /refunds/{refundId} and the cancel response omit `id`
 * and sometimes `referenceId`; the service fills `id` from the path.
 */
final readonly class RefundData
{
    public function __construct(
        public string $id,
        public ?string $referenceId,
        public ?string $linkId,
        public string $reason,
        public int $amount,
        public ?string $initiatedBy,
        public RefundStatus $status,
        public ?RefundMethod $refundMethod,
        public ?string $accountNumber,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $method = isset($data['refundMethod']) && is_string($data['refundMethod']) && $data['refundMethod'] !== ''
            ? RefundMethod::from($data['refundMethod'])
            : null;

        return new self(
            id: (string) $data['id'],
            referenceId: isset($data['referenceId']) ? (string) $data['referenceId'] : null,
            linkId: isset($data['linkId']) ? (string) $data['linkId'] : null,
            reason: (string) ($data['reason'] ?? ''),
            amount: Amount::toInt($data['amount'] ?? 0),
            initiatedBy: isset($data['initiatedBy']) ? (string) $data['initiatedBy'] : null,
            status: RefundStatus::from((string) $data['status']),
            refundMethod: $method,
            accountNumber: isset($data['accountNumber']) ? (string) $data['accountNumber'] : null,
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

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'referenceId' => $this->referenceId,
            'linkId' => $this->linkId,
            'reason' => $this->reason,
            'amount' => $this->amount,
            'initiatedBy' => $this->initiatedBy,
            'status' => $this->status->value,
            'refundMethod' => $this->refundMethod?->value,
            'accountNumber' => $this->accountNumber,
        ];
    }
}
