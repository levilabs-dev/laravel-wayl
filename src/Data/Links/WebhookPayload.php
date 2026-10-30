<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Links;

use LeviLabs\LaravelWayl\Exceptions\WaylException;

/**
 * A webhook body, parsed only after the raw bytes have been verified.
 *
 * Wayl's OpenAPI spec does not document this payload. The fields below are
 * the ones Wayl's own integrations send: `id` (dedupe key), `event`
 * (observed value `order.created`), `referenceId`, and `paymentStatus`.
 * `paymentStatus` uses "Paid", which is not a link status. Anything else
 * stays on `raw`. Confirm fulfilment with WaylLink::get().
 */
final readonly class WebhookPayload
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $id,
        public ?string $event,
        public ?string $referenceId,
        public ?string $paymentStatus,
        public array $raw,
    ) {}

    public static function fromRawBody(string $rawBody): self
    {
        $decoded = json_decode($rawBody, true);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw WaylException::invalidWebhookBody();
        }

        /** @var array<string, mixed> $decoded */
        return new self(
            id: self::stringOrNull($decoded['id'] ?? null),
            event: self::stringOrNull($decoded['event'] ?? null),
            referenceId: self::stringOrNull($decoded['referenceId'] ?? null),
            paymentStatus: self::stringOrNull($decoded['paymentStatus'] ?? null),
            raw: $decoded,
        );
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'Paid';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }
}
