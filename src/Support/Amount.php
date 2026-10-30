<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Support;

use InvalidArgumentException;

final class Amount
{
    public static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) round((float) $value);
        }

        throw new InvalidArgumentException('Wayl returned a non-numeric amount.');
    }
}
