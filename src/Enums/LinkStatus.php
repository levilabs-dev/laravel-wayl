<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Enums;

/**
 * Wayl's link status values, exactly as named in the OpenAPI spec.
 *
 * Wayl documents the names and nothing else — no meanings and no transition
 * graph. isTerminal() / isPaid() below are a conservative reading used by
 * the sync command and by isSuccessful(): Complete and Delivered count as
 * paid, and Cancelled, Rejected, and Returned are treated as finished.
 * Created, Pending, and Processing stay open so the sync command keeps
 * asking Wayl about them.
 *
 * The webhook field paymentStatus uses a different word ("Paid"). Do not
 * compare a webhook payload against this enum.
 */
enum LinkStatus: string
{
    case Created = 'Created';
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Complete = 'Complete';
    case Delivered = 'Delivered';
    case Cancelled = 'Cancelled';
    case Rejected = 'Rejected';
    case Returned = 'Returned';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Complete, self::Delivered, self::Cancelled, self::Rejected, self::Returned => true,
            self::Created, self::Pending, self::Processing => false,
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Complete || $this === self::Delivered;
    }
}
