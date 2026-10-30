<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Refunds;

final readonly class RefundPage
{
    /**
     * @param  array<int, RefundData>  $refunds
     */
    public function __construct(
        public array $refunds,
        public string $message,
    ) {}
}
