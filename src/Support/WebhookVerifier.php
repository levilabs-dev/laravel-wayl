<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Support;

/**
 * Verifies the webhook signature Wayl sends with a payment update.
 *
 * Wayl computes HMAC-SHA256 over the raw request body and sends the digest
 * as bare hex in `x-wayl-signature-256`. Wayl's own plugins also read
 * `x-wayl-signature`, so accept whichever header arrived. There is no
 * `sha256=` prefix and the digest is not base64.
 *
 * Compare the raw bytes. Re-encoding the parsed JSON changes whitespace
 * and key order, and the digest will not match. The secret is the
 * webhookSecret sent when that link was created.
 */
final class WebhookVerifier
{
    public static function verify(string $rawBody, string $signature, string $secret): bool
    {
        $signature = trim($signature);

        if ($rawBody === '' || $signature === '' || $secret === '') {
            return false;
        }

        if (preg_match('/^[0-9a-fA-F]{64}$/', $signature) !== 1) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, strtolower($signature));
    }
}
