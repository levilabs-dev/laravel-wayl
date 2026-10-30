<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Enums\LinkStatus;
use LeviLabs\LaravelWayl\Enums\RefundStatus;
use LeviLabs\LaravelWayl\Models\WaylLink;
use LeviLabs\LaravelWayl\Models\WaylRefund;

it('re-checks only open links and requested refunds', function () {
    WaylLink::query()->create([
        'merchant' => 'default',
        'reference_id' => 'order-open',
        'wayl_id' => 'link-open',
        'status' => LinkStatus::Pending,
        'total' => 10000,
        'currency' => 'IQD',
    ]);

    WaylLink::query()->create([
        'merchant' => 'default',
        'reference_id' => 'order-done',
        'wayl_id' => 'link-done',
        'status' => LinkStatus::Complete,
        'total' => 10000,
        'currency' => 'IQD',
    ]);

    WaylRefund::query()->create([
        'merchant' => 'default',
        'refund_id' => 'refund-open',
        'reference_id' => 'order-done',
        'reason' => str_repeat('a', 100),
        'amount' => 10000,
        'status' => RefundStatus::Requested,
    ]);

    WaylRefund::query()->create([
        'merchant' => 'default',
        'refund_id' => 'refund-done',
        'reference_id' => 'order-done',
        'reason' => str_repeat('a', 100),
        'amount' => 10000,
        'status' => RefundStatus::Refunded,
    ]);

    Http::fake([
        'https://api.thewayl.com/api/v1/links/order-open' => Http::response([
            'data' => waylLinkBody([
                'referenceId' => 'order-open',
                'id' => 'link-open',
                'status' => 'Complete',
            ]),
            'message' => 'Done',
        ], 200),
        'https://api.thewayl.com/api/v1/refunds/refund-open' => Http::response([
            'data' => waylRefundBody([
                'id' => 'refund-open',
                'referenceId' => 'order-done',
                'status' => 'Refunded',
            ]),
            'message' => 'Done',
        ], 200),
    ]);

    $this->artisan('wayl:sync-statuses')->assertSuccessful();

    Http::assertSentCount(2);

    expect(WaylLink::query()->where('reference_id', 'order-open')->value('status'))->toBe(LinkStatus::Complete)
        ->and(WaylRefund::query()->where('refund_id', 'refund-open')->value('status'))->toBe(RefundStatus::Refunded);
});

it('keeps going after one link fails to re-check', function () {
    WaylLink::query()->create([
        'merchant' => 'default',
        'reference_id' => 'order-missing',
        'wayl_id' => 'link-missing',
        'status' => LinkStatus::Created,
        'total' => 10000,
        'currency' => 'IQD',
    ]);

    Http::fake([
        'https://api.thewayl.com/api/v1/links/*' => Http::response([
            'message' => 'Link not found.',
        ], 404),
    ]);

    $this->artisan('wayl:sync-statuses')->assertSuccessful();
});
