<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Exceptions;

use InvalidArgumentException;
use RuntimeException;

class WaylException extends RuntimeException
{
    /**
     * HTTP status from Wayl, when this exception came from requestFailed().
     * Null for failures raised locally before a response arrived.
     */
    public readonly ?int $statusCode;

    /**
     * Wayl's `errors` array, when the body included one (validation
     * failures use this shape). Null otherwise.
     *
     * @var array<int|string, mixed>|null
     */
    public readonly ?array $errors;

    /**
     * @param  array<int|string, mixed>|null  $errors
     */
    public function __construct(string $message, ?int $statusCode = null, ?array $errors = null)
    {
        parent::__construct($message);

        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    /**
     * @param  array<int|string, mixed>|null  $errors
     */
    public static function requestFailed(string $action, int $status, ?string $message, ?array $errors = null): self
    {
        $detail = $message ?? 'the API returned no error message';

        return new self("Wayl {$action} request failed (HTTP {$status}): {$detail}", $status, $errors);
    }

    public static function unexpectedResponse(string $action): self
    {
        return new self("Wayl {$action} response did not contain the expected data envelope.");
    }

    public static function missingWebhookSecret(): self
    {
        return new self(
            'No Wayl webhook secret is configured. Set WAYL_WEBHOOK_SECRET or pass the secret that was used when the link was created.'
        );
    }

    public static function invalidWebhookBody(): self
    {
        return new self('Wayl webhook body is not a JSON object. Verify the signature against the raw body, then parse that same string.');
    }

    public static function invalidReferenceId(string $referenceId, int $min): InvalidArgumentException
    {
        return new InvalidArgumentException(
            "Reference ID [{$referenceId}] must be between {$min} and 255 characters."
        );
    }

    public static function amountBelowMinimum(string $field, int $amount): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl {$field} must be at least 1000 IQD, got {$amount}.");
    }

    public static function unsupportedCurrency(string $currency): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl currently only supports IQD, got [{$currency}].");
    }

    public static function invalidEnvironment(string $environment): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl environment [{$environment}] must be \"test\" or \"live\".");
    }

    public static function invalidUrl(string $field): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl {$field} must be a valid URL.");
    }

    public static function invalidWebhookSecretLength(int $length): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl webhookSecret must be between 10 and 255 characters, got {$length}.");
    }

    public static function lineItemsDoNotReconcile(int $total, int $signed, int $gross): InvalidArgumentException
    {
        return new InvalidArgumentException(
            "Line items must add up to the link total of {$total} IQD. "
            ."The signed total (increase minus decrease) is {$signed} and the gross total is {$gross}."
        );
    }

    public static function invalidBatchSize(int $count): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl batch retrieval accepts 1 to 100 reference IDs, got {$count}.");
    }

    public static function invalidRefundReason(int $length): InvalidArgumentException
    {
        return new InvalidArgumentException("Wayl refund reason must be between 100 and 1500 characters, got {$length}.");
    }

    public static function refundDestinationMismatch(): InvalidArgumentException
    {
        return new InvalidArgumentException('Wayl refundMethod and accountNumber must be provided together, or both omitted to refund the original payment method.');
    }
}
