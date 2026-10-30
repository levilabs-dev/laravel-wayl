<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Contracts\WaylProductServiceContract;

it('lists products', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/products*' => Http::response([
            'data' => [[
                'id' => 'prod-1',
                'name' => 'Wireless keyboard',
                'price' => '30000',
                'url' => 'https://wayl.io/p/prod-1',
                'status' => 'Active',
                'image' => 'https://cdn.example.test/keyboard.jpg',
                'tags' => 'electronics',
                'description' => 'A keyboard',
                'createdAt' => '2025-12-23T09:01:58.032Z',
                'updatedAt' => '2025-12-23T09:01:58.032Z',
                'productType' => 'Physical',
                'downloadable' => '',
                'discountType' => 'percentage',
                'discountExpiry' => '2026-01-01',
                'discountValue' => '10',
                'inventories' => [['sku' => 'KB-1', 'qt' => 4]],
                'qt' => 4,
                'unlimited' => false,
                'notifyOn' => 2,
            ]],
            'message' => 'Done',
        ], 200),
    ]);

    $page = app(WaylProductServiceContract::class)->list(take: 5, skip: 0);
    $product = $page->products[0];

    expect($page->message)->toBe('Done')
        ->and($product->name)->toBe('Wireless keyboard')
        ->and($product->productType)->toBe('Physical')
        ->and($product->quantity)->toBe(4)
        ->and($product->unlimited)->toBeFalse()
        ->and($product->inventories[0]['sku'])->toBe('KB-1')
        ->and($product->toArray()['price'])->toBe('30000');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'take=5')
        && str_contains($request->url(), 'skip=0'));
});

it('retrieves one product', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/products/*' => Http::response([
            'data' => [
                'id' => 'prod-1',
                'name' => 'Wireless keyboard',
                'price' => '30000',
                'status' => 'Active',
                'url' => 'https://wayl.io/p/prod-1',
                'image' => 'https://cdn.example.test/keyboard.jpg',
                'tags' => 'electronics',
                'description' => 'A keyboard',
            ],
            'message' => 'Done',
        ], 200),
    ]);

    $product = app(WaylProductServiceContract::class)->get('prod-1');

    expect($product->id)->toBe('prod-1')
        ->and($product->status)->toBe('Active')
        ->and($product->quantity)->toBeNull();
});
