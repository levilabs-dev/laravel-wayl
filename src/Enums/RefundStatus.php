<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Enums;

/**
 * Wayl's refund status values, exactly as named in the OpenAPI spec.
 * Only a Requested refund can be cancelled.
 */
enum RefundStatus: string
{
    case Requested = 'Requested';
    case Refunded = 'Refunded';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';

    public function isTerminal(): bool
    {
        return $this !== self::Requested;
    }

    public function isSuccessful(): bool
    {
        return $this === self::Refunded;
    }
}
