<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Products;

final readonly class ProductPage
{
    /**
     * @param  array<int, ProductData>  $products
     */
    public function __construct(
        public array $products,
        public string $message,
    ) {}
}
