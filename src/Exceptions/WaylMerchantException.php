<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Exceptions;

use RuntimeException;

class WaylMerchantException extends RuntimeException
{
    public static function unknownMerchant(string $merchant): self
    {
        return new self("Wayl merchant [{$merchant}] is not configured in config/wayl.php.");
    }

    public static function missingCredentials(string $merchant): self
    {
        return new self(
            "Wayl merchant [{$merchant}] is missing an API key — set WAYL_API_KEY."
        );
    }
}
