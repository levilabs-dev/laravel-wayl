<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelWayl\Contracts\WaylRefundServiceContract;
use LeviLabs\LaravelWayl\Data\Refunds\RefundData;
use LeviLabs\LaravelWayl\Data\Refunds\RefundPage;
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;

/**
 * @method static RefundData create(string $referenceId, string $reason, int $amount, ?RefundMethod $refundMethod = null, ?string $accountNumber = null, ?string $merchant = null)
 * @method static RefundData get(string $refundId, ?string $merchant = null)
 * @method static RefundPage list(?string $referenceId = null, ?int $take = null, ?int $skip = null, ?array<int, RefundStatus> $statuses = null, ?string $merchant = null)
 * @method static RefundData cancel(string $refundId, ?string $merchant = null)
 *
 * @see WaylRefundServiceContract
 */
class WaylRefund extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WaylRefundServiceContract::class;
    }
}
