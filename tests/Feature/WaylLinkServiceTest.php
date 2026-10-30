<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Data\Links\LineItem;
use LeviLabs\LaravelWayl\Data\Links\LinkData;
use LeviLabs\LaravelWayl\Enums\LineItemType;
use LeviLabs\LaravelWayl\Enums\LinkStatus;
use LeviLabs\LaravelWayl\Exceptions\WaylException;
use LeviLabs\LaravelWayl\Exceptions\WaylMerchantException;
use LeviLabs\LaravelWayl\Models\WaylLink;

it('creates a link and persists the code, environment, and encrypted webhook secret', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links' => Http::response([
            'data' => waylLinkBody(),
            'message' => 'Done',
        ], 201),
    ]);

    $link = app(WaylLinkServiceContract::class)->create(
        referenceId: 'order-42',
        total: 10000,
        lineItems: [
            new LineItem('Basket', 12000, LineItemType::Increase),
            new LineItem('Discount', 2000, LineItemType::Decrease),
        ],
        customParameter: 'order-42',
    );

    expect($link)->toBeInstanceOf(LinkData::class)
        ->and($link->status)->toBe(LinkStatus::Created)
        ->and($link->code)->toBe('7G2DG958')
        ->and($link->url)->toContain('cmjicw32o00jnjy089lh5kvri')
        ->and($link->isPaid())->toBeFalse();

    $stored = WaylLink::query()->where('reference_id', 'order-42')->first();

    expect($stored)->not->toBeNull()
        ->and($stored->status)->toBe(LinkStatus::Created)
        ->and($stored->total)->toBe(10000)
        ->and($stored->code)->toBe('7G2DG958')
        ->and($stored->environment)->toBe('test')
        ->and($stored->webhook_secret)->toBe('1234567890')
        ->and(DB::table('wayl_links')->value('webhook_secret'))->not->toBe('1234567890');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/v1/links')
            && $request->hasHeader('X-WAYL-AUTHENTICATION', 'test-wayl-key')
            && $data['env'] === 'test'
            && $data['lineItem'][0]['label'] === 'Basket'
            && $data['lineItem'][1]['type'] === 'decrease'
            && $data['webhookSecret'] === '1234567890'
            && $data['linkExpiresIn'] === '1h';
    });
});

it('accepts a gross line-item total when decrease rows are not subtracted', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links' => Http::response([
            'data' => waylLinkBody(),
            'message' => 'Done',
        ], 201),
    ]);

    app(WaylLinkServiceContract::class)->create(
        referenceId: 'order-42',
        total: 10000,
        lineItems: [
            new LineItem('Basket', 6000, LineItemType::Increase),
            new LineItem('Fee', 4000, LineItemType::Decrease),
        ],
    );

    Http::assertSentCount(1);
});

it('rejects a total below 1000 before sending a request', function () {
    app(WaylLinkServiceContract::class)->create(referenceId: 'order-42', total: 500);
})->throws(InvalidArgumentException::class, 'at least 1000');

it('rejects line items that match neither the signed nor the gross total', function () {
    app(WaylLinkServiceContract::class)->create(
        referenceId: 'order-42',
        total: 10000,
        lineItems: [new LineItem('Basket', 5000, LineItemType::Increase)],
    );
})->throws(InvalidArgumentException::class);

it('rejects a line item label that is too short', function () {
    new LineItem('ab', 1000, LineItemType::Increase);
})->throws(InvalidArgumentException::class);

it('rejects an expiry outside 1m to 30d before sending a request', function () {
    app(WaylLinkServiceContract::class)->create(
        referenceId: 'order-42',
        total: 10000,
        linkExpiresIn: '31d',
    );
})->throws(InvalidArgumentException::class, '30d');

it('rejects a currency other than IQD', function () {
    app(WaylLinkServiceContract::class)->create(referenceId: 'order-42', total: 10000, currency: 'USD');
})->throws(InvalidArgumentException::class, 'IQD');

it('throws with the HTTP status and validation errors when Wayl rejects the call', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links' => Http::response([
            'message' => 'Reference ID already exists.',
            'errors' => [['field' => 'referenceId']],
        ], 422),
    ]);

    try {
        app(WaylLinkServiceContract::class)->create(referenceId: 'order-42', total: 10000);
    } catch (WaylException $e) {
        expect($e->statusCode)->toBe(422)
            ->and($e->getMessage())->toContain('Reference ID already exists.')
            ->and($e->errors)->toBe([['field' => 'referenceId']]);

        return;
    }

    $this->fail('Expected WaylException was not thrown.');
});

it('treats an HTTP 200 body with success false as a failure', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/verify-auth-key' => Http::response([
            'success' => false,
            'message' => 'Invalid authentication key.',
        ], 200),
    ]);

    app(WaylLinkServiceContract::class)->verify();
})->throws(WaylException::class, 'Invalid authentication key.');

it('verifies the merchant key and returns Wayl\'s message', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/verify-auth-key' => Http::response([
            'data' => [],
            'message' => 'Authentication key is valid.',
        ], 200),
    ]);

    expect(app(WaylLinkServiceContract::class)->verify())->toBe('Authentication key is valid.');
});

it('fetches a link by reference id and keeps the code stored at creation', function () {
    WaylLink::query()->create([
        'merchant' => 'default',
        'reference_id' => 'order/42',
        'wayl_id' => 'cmjicw32o00jnjy089lh5kvri',
        'code' => '7G2DG958',
        'status' => LinkStatus::Created,
        'total' => 10000,
        'currency' => 'IQD',
        'environment' => 'test',
        'webhook_secret' => '1234567890',
    ]);

    Http::fake([
        'https://api.thewayl.com/api/v1/links/*' => Http::response([
            'data' => waylLinkBody([
                'referenceId' => 'order/42',
                'status' => 'Complete',
                'completedAt' => '2025-12-23T10:00:00.000Z',
                'paymentMethod' => 'QiCard',
                'code' => null,
            ]),
            'message' => 'Done',
        ], 200),
    ]);

    $link = app(WaylLinkServiceContract::class)->get('order/42');

    expect($link->isPaid())->toBeTrue()
        ->and($link->paymentMethod)->toBe('QiCard');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/links/order%2F42'));

    $stored = WaylLink::query()->where('reference_id', 'order/42')->first();

    expect($stored->status)->toBe(LinkStatus::Complete)
        ->and($stored->code)->toBe('7G2DG958')
        ->and($stored->webhook_secret)->toBe('1234567890')
        ->and($stored->checked_at)->not->toBeNull();
});

it('lists links with exploded status filters', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links*' => Http::response([
            'data' => [waylLinkBody(['status' => 'Pending'])],
            'message' => 'Done',
        ], 200),
    ]);

    $page = app(WaylLinkServiceContract::class)->list(
        take: 10,
        statuses: [LinkStatus::Pending, LinkStatus::Complete],
    );

    expect($page->links)->toHaveCount(1)
        ->and($page->message)->toBe('Done')
        ->and(WaylLink::query()->count())->toBe(1);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'statuses=Pending')
            && str_contains($request->url(), 'statuses=Complete')
            && str_contains($request->url(), 'take=10')
            && ! str_contains($request->url(), 'statuses%5B');
    });
});

it('retrieves a batch of links and reports how many were found', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links/batch' => Http::response([
            'data' => [waylLinkBody()],
            'message' => 'Found 1 links out of 2 requested reference IDs',
            'totalRequested' => 2,
            'totalFound' => 1,
        ], 200),
    ]);

    $batch = app(WaylLinkServiceContract::class)->batch(['order-42', 'missing-order']);

    expect($batch->totalRequested)->toBe(2)
        ->and($batch->totalFound)->toBe(1)
        ->and($batch->links[0]->referenceId)->toBe('order-42');
});

it('rejects a batch outside 1 to 100 reference ids', function () {
    app(WaylLinkServiceContract::class)->batch([]);
})->throws(InvalidArgumentException::class, '1 to 100');

it('invalidates an unpaid link', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links/*/invalidate' => Http::response([
            'data' => waylLinkBody(['status' => 'Cancelled']),
            'message' => 'Done',
        ], 201),
    ]);

    $link = app(WaylLinkServiceContract::class)->invalidate('order-42');

    expect($link->status)->toBe(LinkStatus::Cancelled)
        ->and(WaylLink::query()->value('status'))->toBe(LinkStatus::Cancelled);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_contains($request->url(), '/invalidate')
        && ! str_contains($request->url(), 'invalidate-if-pending'));
});

it('invalidates a link only when it is still pending', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links/*/invalidate-if-pending' => Http::response([
            'data' => waylLinkBody(['status' => 'Pending']),
            'message' => 'Done',
        ], 201),
    ]);

    $link = app(WaylLinkServiceContract::class)->invalidateIfPending('order-42');

    expect($link->status)->toBe(LinkStatus::Pending);
});

it('round-trips a link DTO through toArray()', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/links' => Http::response([
            'data' => waylLinkBody(),
            'message' => 'Done',
        ], 201),
    ]);

    $array = app(WaylLinkServiceContract::class)->create(referenceId: 'order-42', total: 10000)->toArray();

    expect($array['id'])->toBe('cmjicw32o00jnjy089lh5kvri')
        ->and($array['status'])->toBe('Created')
        ->and($array['total'])->toBe(10000)
        ->and(json_encode($array))->not->toBeFalse();
});

it('fails before a request when the merchant is unknown or has no API key', function () {
    expect(fn () => app(WaylLinkServiceContract::class)->get('order-42', merchant: 'other'))
        ->toThrow(WaylMerchantException::class, 'other');

    config(['wayl.merchants.default.api_key' => null]);

    expect(fn () => app(WaylLinkServiceContract::class)->verify())
        ->toThrow(WaylMerchantException::class, 'WAYL_API_KEY');
});
