<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Services;

use LeviLabs\LaravelWayl\Contracts\WaylProductServiceContract;
use LeviLabs\LaravelWayl\Data\Products\ProductData;
use LeviLabs\LaravelWayl\Data\Products\ProductPage;
use LeviLabs\LaravelWayl\Services\Concerns\TalksToWayl;

final class WaylProductService implements WaylProductServiceContract
{
    use TalksToWayl;

    public function list(?int $take = null, ?int $skip = null, ?string $merchant = null): ProductPage
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'product list',
            'get',
            $this->withQuery('/api/v1/products', [
                'take' => $take,
                'skip' => $skip,
            ]),
            idempotent: true,
        );

        return new ProductPage(
            ProductData::collection($this->collection($envelope, 'product list')),
            $this->message($envelope),
        );
    }

    public function get(string $productId, ?string $merchant = null): ProductData
    {
        $merchant ??= $this->defaultMerchant();

        $envelope = $this->send(
            $merchant,
            'product retrieval',
            'get',
            '/api/v1/products/'.$this->encode($productId),
            idempotent: true,
        );

        return ProductData::fromArray($this->resource($envelope, 'product retrieval'));
    }
}
