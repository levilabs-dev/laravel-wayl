<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Contracts;

use LeviLabs\LaravelWayl\Data\Products\ProductData;
use LeviLabs\LaravelWayl\Data\Products\ProductPage;

interface WaylProductServiceContract
{
    /**
     * Newest products first. Subscription products are not included.
     * skip and take paginate (Wayl defaults to 0 and 10).
     */
    public function list(?int $take = null, ?int $skip = null, ?string $merchant = null): ProductPage;

    public function get(string $productId, ?string $merchant = null): ProductData;
}
