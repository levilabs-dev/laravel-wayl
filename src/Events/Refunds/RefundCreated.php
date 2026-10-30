<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Events\Refunds;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelWayl\Data\Refunds\RefundData;

final class RefundCreated
{
    use Dispatchable;

    public function __construct(
        public readonly RefundData $refund,
        public readonly string $merchant,
    ) {}
}
