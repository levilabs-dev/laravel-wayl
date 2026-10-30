<?php

declare(strict_types=1);

use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Data\Links\WebhookPayload;
use LeviLabs\LaravelWayl\Exceptions\WaylException;
use LeviLabs\LaravelWayl\Support\WebhookVerifier;

const WEBHOOK_SECRET = '1234567890';

const WEBHOOK_BODY = '{"id":"evt_1","event":"order.created","referenceId":"order-42","paymentStatus":"Paid"}';

it('verifies a raw-body HMAC and rejects a re-encoded payload', function () {
    $signature = hash_hmac('sha256', WEBHOOK_BODY, WEBHOOK_SECRET);

    expect(WebhookVerifier::verify(WEBHOOK_BODY, $signature, WEBHOOK_SECRET))->toBeTrue()
        ->and(WebhookVerifier::verify(WEBHOOK_BODY, strtoupper($signature), WEBHOOK_SECRET))->toBeTrue();

    $reencoded = json_encode(json_decode(WEBHOOK_BODY, true), JSON_PRETTY_PRINT);

    expect(WebhookVerifier::verify((string) $reencoded, $signature, WEBHOOK_SECRET))->toBeFalse();
});

it('rejects empty input, a sha256 prefix, and a digest that is not 64 hex characters', function () {
    $signature = hash_hmac('sha256', WEBHOOK_BODY, WEBHOOK_SECRET);

    expect(WebhookVerifier::verify('', $signature, WEBHOOK_SECRET))->toBeFalse()
        ->and(WebhookVerifier::verify(WEBHOOK_BODY, '', WEBHOOK_SECRET))->toBeFalse()
        ->and(WebhookVerifier::verify(WEBHOOK_BODY, $signature, ''))->toBeFalse()
        ->and(WebhookVerifier::verify(WEBHOOK_BODY, 'sha256='.$signature, WEBHOOK_SECRET))->toBeFalse()
        ->and(WebhookVerifier::verify(WEBHOOK_BODY, 'abcd', WEBHOOK_SECRET))->toBeFalse();
});

it('verifies through the service using the configured secret', function () {
    $signature = hash_hmac('sha256', WEBHOOK_BODY, WEBHOOK_SECRET);

    expect(app(WaylLinkServiceContract::class)->verifyWebhookSignature(WEBHOOK_BODY, $signature))->toBeTrue();
});

it('requires a secret when none is configured', function () {
    config(['wayl.webhook_secret' => null]);

    app(WaylLinkServiceContract::class)->verifyWebhookSignature(WEBHOOK_BODY, hash_hmac('sha256', WEBHOOK_BODY, WEBHOOK_SECRET));
})->throws(WaylException::class, 'WAYL_WEBHOOK_SECRET');

it('reads paymentStatus Paid without treating it as a link status', function () {
    $payload = WebhookPayload::fromRawBody(WEBHOOK_BODY);

    expect($payload->isPaid())->toBeTrue()
        ->and($payload->id)->toBe('evt_1')
        ->and($payload->event)->toBe('order.created')
        ->and($payload->referenceId)->toBe('order-42')
        ->and($payload->paymentStatus)->toBe('Paid');
});

it('rejects a webhook body that is not a JSON object', function () {
    WebhookPayload::fromRawBody('[1,2,3]');
})->throws(WaylException::class);
