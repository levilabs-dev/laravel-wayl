<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->beforeEach(function () {
    Http::preventStrayRequests();
})->in(__DIR__);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function waylLinkBody(array $overrides = []): array
{
    return array_replace([
        'customParameter' => 'order-42',
        'referenceId' => 'order-42',
        'id' => 'cmjicw32o00jnjy089lh5kvri',
        'code' => '7G2DG958',
        'total' => '10000',
        'currency' => 'IQD',
        'type' => 'Order',
        'paymentMethod' => null,
        'reversable' => false,
        'status' => 'Created',
        'completedAt' => null,
        'createdAt' => '2025-12-23T09:01:58.032Z',
        'updatedAt' => '2025-12-23T09:01:58.032Z',
        'url' => 'https://link.thewayl.com/pay?id=cmjicw32o00jnjy089lh5kvri',
        'webhookUrl' => 'https://example.test/wayl/webhook',
        'redirectionUrl' => 'https://example.test/checkout/return?referenceId=order-42&orderid=cmjicw32o00jnjy089lh5kvri',
        'linkExpiresIn' => '1h',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function waylRefundBody(array $overrides = []): array
{
    return array_replace([
        'id' => 'refund-1',
        'reason' => str_repeat('Customer changed their mind. ', 8),
        'linkId' => 'cmjicw32o00jnjy089lh5kvri',
        'referenceId' => 'order-42',
        'amount' => 10000,
        'initiatedBy' => 'Merchant',
        'status' => 'Requested',
        'refundMethod' => null,
        'accountNumber' => null,
    ], $overrides);
}
