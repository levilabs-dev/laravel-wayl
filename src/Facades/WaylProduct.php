<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelWayl\Contracts\WaylProductServiceContract;
use LeviLabs\LaravelWayl\Data\Products\ProductData;
use LeviLabs\LaravelWayl\Data\Products\ProductPage;

/**
 * @method static ProductPage list(?int $take = null, ?int $skip = null, ?string $merchant = null)
 * @method static ProductData get(string $productId, ?string $merchant = null)
 *
 * @see WaylProductServiceContract
 */
class WaylProduct extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WaylProductServiceContract::class;
    }
}
