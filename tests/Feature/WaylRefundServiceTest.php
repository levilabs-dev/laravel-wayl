<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Contracts\WaylRefundServiceContract;
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;
use LeviLabs\LaravelWayl\Models\WaylLink;
use LeviLabs\LaravelWayl\Models\WaylRefund;

it('creates a refund, links it to the stored payment, and persists it', function () {
    $link = WaylLink::query()->create([
        'merchant' => 'default',
        'reference_id' => 'order-42',
        'wayl_id' => 'cmjicw32o00jnjy089lh5kvri',
        'status' => 'Complete',
        'total' => 10000,
        'currency' => 'IQD',
    ]);

    Http::fake([
        'https://api.thewayl.com/api/v1/refunds' => Http::response([
            'data' => waylRefundBody([
                'refundMethod' => 'FIB',
                'accountNumber' => 'ACC-9',
            ]),
            'message' => 'Done',
        ], 201),
    ]);

    $reason = str_repeat('Customer changed their mind. ', 8);

    $refund = app(WaylRefundServiceContract::class)->create(
        referenceId: 'order-42',
        reason: $reason,
        amount: 10000,
        refundMethod: RefundMethod::Fib,
        accountNumber: 'ACC-9',
    );

    expect($refund->isSuccessful())->toBeFalse()
        ->and($refund->status)->toBe(RefundStatus::Requested)
        ->and($refund->refundMethod)->toBe(RefundMethod::Fib);

    $stored = WaylRefund::query()->where('refund_id', 'refund-1')->first();

    expect($stored->link_id)->toBe($link->id)
        ->and($stored->status)->toBe(RefundStatus::Requested)
        ->and($stored->account_number)->toBe('ACC-9');

    Http::assertSent(function (Request $request) use ($reason): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $data['reason'] === $reason
            && $data['refundMethod'] === 'FIB'
            && $data['amount'] === 10000;
    });
});

it('rejects a refund reason shorter than 100 characters', function () {
    app(WaylRefundServiceContract::class)->create(
        referenceId: 'order-42',
        reason: 'Too short',
        amount: 10000,
    );
})->throws(InvalidArgumentException::class, '100 and 1500');

it('rejects a refund method sent without an account number', function () {
    app(WaylRefundServiceContract::class)->create(
        referenceId: 'order-42',
        reason: str_repeat('a', 100),
        amount: 10000,
        refundMethod: RefundMethod::QiCard,
    );
})->throws(InvalidArgumentException::class, 'together');

it('fills the refund id from the path when the response omits it', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/refunds/*' => Http::response([
            'data' => waylRefundBody(['id' => null, 'status' => 'Refunded']),
            'message' => 'Done',
        ], 200),
    ]);

    $refund = app(WaylRefundServiceContract::class)->get('refund-1');

    expect($refund->id)->toBe('refund-1')
        ->and($refund->isSuccessful())->toBeTrue()
        ->and(WaylRefund::query()->where('refund_id', 'refund-1')->value('status'))->toBe(RefundStatus::Refunded);
});

it('lists refunds for a reference id', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/refunds*' => Http::response([
            'data' => [waylRefundBody()],
            'message' => 'Done',
        ], 200),
    ]);

    $page = app(WaylRefundServiceContract::class)->list(
        referenceId: 'order-42',
        statuses: [RefundStatus::Requested],
    );

    expect($page->refunds)->toHaveCount(1);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'referenceId=order-42')
        && str_contains($request->url(), 'statuses=Requested'));
});

it('cancels a requested refund with DELETE', function () {
    Http::fake([
        'https://api.thewayl.com/api/v1/refunds/*/cancel' => Http::response([
            'data' => [
                'reason' => str_repeat('Customer changed their mind. ', 8),
                'linkId' => 'cmjicw32o00jnjy089lh5kvri',
                'amount' => 10000,
                'initiatedBy' => 'Merchant',
                'status' => 'Cancelled',
            ],
            'message' => 'Done',
        ], 201),
    ]);

    $refund = app(WaylRefundServiceContract::class)->cancel('refund-1');

    expect($refund->status)->toBe(RefundStatus::Cancelled)
        ->and($refund->id)->toBe('refund-1')
        ->and(WaylRefund::query()->value('status'))->toBe(RefundStatus::Cancelled);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), '/refunds/refund-1/cancel'));
});
