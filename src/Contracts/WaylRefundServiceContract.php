<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Contracts;

use LeviLabs\LaravelWayl\Data\Refunds\RefundData;
use LeviLabs\LaravelWayl\Data\Refunds\RefundPage;
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;

interface WaylRefundServiceContract
{
    /**
     * Requests a refund for a paid order. The reason must be 100–1500
     * characters and the amount is whole IQD, at least 1000, and no greater
     * than what the gateway collected. Pass refundMethod and accountNumber
     * together to send the money somewhere other than the original method.
     * This moves real money — put your own approval step in front of it.
     */
    public function create(
        string $referenceId,
        string $reason,
        int $amount,
        ?RefundMethod $refundMethod = null,
        ?string $accountNumber = null,
        ?string $merchant = null,
    ): RefundData;

    public function get(string $refundId, ?string $merchant = null): RefundData;

    /**
     * Newest refunds first. Filter by the order reference ID and/or status.
     *
     * @param  array<int, RefundStatus>|null  $statuses
     */
    public function list(
        ?string $referenceId = null,
        ?int $take = null,
        ?int $skip = null,
        ?array $statuses = null,
        ?string $merchant = null,
    ): RefundPage;

    /**
     * Cancels a refund that is still Requested.
     */
    public function cancel(string $refundId, ?string $merchant = null): RefundData;
}
