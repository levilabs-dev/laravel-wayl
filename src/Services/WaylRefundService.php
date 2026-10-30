<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Services;

use LeviLabs\LaravelWayl\Contracts\WaylRefundServiceContract;
use LeviLabs\LaravelWayl\Data\Refunds\RefundData;
use LeviLabs\LaravelWayl\Data\Refunds\RefundPage;
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCancelled;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCreated;
use LeviLabs\LaravelWayl\Events\Refunds\RefundStatusChecked;
use LeviLabs\LaravelWayl\Exceptions\WaylException;
use LeviLabs\LaravelWayl\Services\Concerns\TalksToWayl;

final class WaylRefundService implements WaylRefundServiceContract
{
    use TalksToWayl;

    public function create(
        string $referenceId,
        string $reason,
        int $amount,
        ?RefundMethod $refundMethod = null,
        ?string $accountNumber = null,
        ?string $merchant = null,
    ): RefundData {
        $this->assertReferenceId($referenceId, 2);
        $this->assertReason($reason);
        $this->assertMinimumAmount($amount, 'refund amount');
        $accountNumber = $this->nullableString($accountNumber);
        $this->assertDestination($refundMethod, $accountNumber);

        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send($merchant, 'refund creation', 'post', '/api/v1/refunds', array_filter([
            'referenceId' => $referenceId,
            'reason' => $reason,
            'amount' => $amount,
            'refundMethod' => $refundMethod?->value,
            'accountNumber' => $accountNumber,
        ], fn (mixed $value): bool => $value !== null));

        $refund = RefundData::fromArray($this->resource($envelope, 'refund creation'));

        RefundCreated::dispatch($refund, $merchant);

        return $refund;
    }

    public function get(string $refundId, ?string $merchant = null): RefundData
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'refund retrieval',
            'get',
            '/api/v1/refunds/'.$this->encode($refundId),
            idempotent: true,
        );

        $data = $this->resource($envelope, 'refund retrieval');
        $data['id'] ??= $refundId;

        $refund = RefundData::fromArray($data);

        RefundStatusChecked::dispatch($refund, $merchant);

        return $refund;
    }

    public function list(
        ?string $referenceId = null,
        ?int $take = null,
        ?int $skip = null,
        ?array $statuses = null,
        ?string $merchant = null,
    ): RefundPage {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'refund list',
            'get',
            $this->withQuery('/api/v1/refunds', [
                'referenceId' => $referenceId,
                'take' => $take,
                'skip' => $skip,
                'statuses' => $this->refundStatusValues($statuses),
            ]),
            idempotent: true,
        );

        $refunds = RefundData::collection($this->collection($envelope, 'refund list'));

        foreach ($refunds as $refund) {
            RefundStatusChecked::dispatch($refund, $merchant);
        }

        return new RefundPage($refunds, $this->message($envelope));
    }

    public function cancel(string $refundId, ?string $merchant = null): RefundData
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'refund cancellation',
            'delete',
            '/api/v1/refunds/'.$this->encode($refundId).'/cancel',
        );

        $data = $this->resource($envelope, 'refund cancellation');
        $data['id'] ??= $refundId;

        $refund = RefundData::fromArray($data);

        RefundCancelled::dispatch($refund, $merchant);

        return $refund;
    }

    private function assertReason(string $reason): void
    {
        $length = mb_strlen($reason);

        if ($length < 100 || $length > 1500) {
            throw WaylException::invalidRefundReason($length);
        }
    }

    private function assertDestination(?RefundMethod $refundMethod, ?string $accountNumber): void
    {
        if (($refundMethod === null) !== ($accountNumber === null)) {
            throw WaylException::refundDestinationMismatch();
        }
    }

    /**
     * @param  array<int, RefundStatus>|null  $statuses
     * @return array<int, string>|null
     */
    private function refundStatusValues(?array $statuses): ?array
    {
        if ($statuses === null || $statuses === []) {
            return null;
        }

        return array_map(fn (RefundStatus $status): string => $status->value, $statuses);
    }
}
