<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Support;

use InvalidArgumentException;

/**
 * Wayl's linkExpiresIn format: a positive integer plus m, h, or d.
 * The window must fall between 1 minute and 30 days, inclusive.
 */
final class LinkExpiry
{
    public static function assertValid(string $value): void
    {
        if (preg_match('/^(\d+)(m|h|d)$/', $value, $matches) !== 1) {
            throw new InvalidArgumentException(
                "Wayl linkExpiresIn [{$value}] must look like \"30m\", \"1h\", or \"7d\"."
            );
        }

        $quantity = (int) $matches[1];

        if ($quantity < 1) {
            throw new InvalidArgumentException("Wayl linkExpiresIn [{$value}] must be at least 1m.");
        }

        $minutesPerUnit = ['m' => 1, 'h' => 60, 'd' => 1440];
        $minutes = $quantity * $minutesPerUnit[$matches[2]];

        if ($minutes > 30 * 1440) {
            throw new InvalidArgumentException("Wayl linkExpiresIn [{$value}] must be 30d or shorter.");
        }
    }
}
