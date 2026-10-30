<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelWayl\Enums\LinkEnvironment;
use LeviLabs\LaravelWayl\Exceptions\WaylException;
use LeviLabs\LaravelWayl\Exceptions\WaylMerchantException;

trait TalksToWayl
{
    /**
     * @return array{api_key: string, base_url: string}
     */
    private function merchantConfig(string $merchant): array
    {
        $config = config("wayl.merchants.{$merchant}");

        if (! is_array($config)) {
            throw WaylMerchantException::unknownMerchant($merchant);
        }

        $apiKey = $config['api_key'] ?? null;

        if (! is_string($apiKey) || $apiKey === '') {
            throw WaylMerchantException::missingCredentials($merchant);
        }

        $baseUrl = $config['base_url'] ?? 'https://api.thewayl.com';

        return [
            'api_key' => $apiKey,
            'base_url' => rtrim(is_string($baseUrl) ? $baseUrl : 'https://api.thewayl.com', '/'),
        ];
    }

    private function client(string $merchant, bool $idempotent): PendingRequest
    {
        $config = $this->merchantConfig($merchant);

        $request = Http::baseUrl($config['base_url'])
            ->withHeaders(['X-WAYL-AUTHENTICATION' => $config['api_key']])
            ->timeout($this->intConfig('wayl.http.timeout', 15))
            ->acceptJson()
            ->asJson();

        if ($idempotent) {
            $request = $request->retry(
                $this->intConfig('wayl.http.retry_times', 1),
                $this->intConfig('wayl.http.retry_sleep_ms', 200),
            );
        }

        return $request;
    }

    /**
     * Sends a request and returns Wayl's JSON envelope. Success responses
     * are `{data, message}` with HTTP 200 or 201. Failures use the HTTP
     * status plus `message` and, on validation errors, `errors`.
     *
     * Retries are limited to idempotent GETs so a timeout cannot create a
     * second link or a second refund.
     *
     * @param  'delete'|'get'|'post'  $method
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function send(string $merchant, string $action, string $method, string $path, ?array $payload = null, bool $idempotent = false): array
    {
        $request = $this->client($merchant, $idempotent);

        $response = match ($method) {
            'post' => $request->post($path, $payload ?? []),
            'delete' => $request->delete($path),
            'get' => $request->get($path),
        };

        $decoded = $response->json();
        $envelope = is_array($decoded) && ! array_is_list($decoded) ? $decoded : null;

        if ($response->failed() || $envelope === null || ($envelope['success'] ?? true) === false) {
            $decodedObject = is_array($decoded) && ! array_is_list($decoded) ? $decoded : [];
            $detail = $this->message($decodedObject);

            throw WaylException::requestFailed(
                $action,
                $response->status(),
                $detail !== '' ? $detail : null,
                $this->errors(is_array($decoded) ? $decoded : []),
            );
        }

        /** @var array<string, mixed> $envelope */
        return $envelope;
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array<string, mixed>
     */
    private function resource(array $envelope, string $action): array
    {
        $data = $envelope['data'] ?? null;

        if (! is_array($data) || array_is_list($data)) {
            throw WaylException::unexpectedResponse($action);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array<int, array<string, mixed>>
     */
    private function collection(array $envelope, string $action): array
    {
        $data = $envelope['data'] ?? null;

        if (! is_array($data) || ! array_is_list($data)) {
            throw WaylException::unexpectedResponse($action);
        }

        $items = [];

        foreach ($data as $item) {
            if (! is_array($item) || array_is_list($item)) {
                throw WaylException::unexpectedResponse($action);
            }

            /** @var array<string, mixed> $item */
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  array<int|string, mixed>  $decoded
     */
    private function message(array $decoded): string
    {
        $message = $decoded['message'] ?? null;

        if (is_string($message)) {
            return $message;
        }

        if (is_array($message)) {
            return json_encode($message) ?: '';
        }

        return '';
    }

    /**
     * @param  array<int|string, mixed>  $decoded
     * @return array<int|string, mixed>|null
     */
    private function errors(array $decoded): ?array
    {
        $errors = $decoded['errors'] ?? null;

        return is_array($errors) ? $errors : null;
    }

    /**
     * OpenAPI query arrays use exploded form style (`statuses=Pending&statuses=Complete`).
     *
     * @param  array<string, array<int, string>|int|string|null>  $query
     */
    private function withQuery(string $path, array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode($key).'='.rawurlencode($item);
                }

                continue;
            }

            $parts[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return $parts === [] ? $path : $path.'?'.implode('&', $parts);
    }

    private function encode(string $value): string
    {
        return rawurlencode($value);
    }

    private function defaultMerchant(): string
    {
        $merchant = config('wayl.default', 'default');

        return is_string($merchant) && $merchant !== '' ? $merchant : 'default';
    }

    private function environment(): LinkEnvironment
    {
        $value = config('wayl.environment', 'test');
        $value = is_string($value) ? $value : 'test';
        $environment = LinkEnvironment::tryFrom($value);

        if ($environment === null) {
            throw WaylException::invalidEnvironment($value);
        }

        return $environment;
    }

    private function assertReferenceId(string $referenceId, int $min = 1): void
    {
        $length = mb_strlen($referenceId);

        if ($length < $min || $length > 255) {
            throw WaylException::invalidReferenceId($referenceId, $min);
        }
    }

    private function assertMinimumAmount(int $amount, string $field): void
    {
        if ($amount < 1000) {
            throw WaylException::amountBelowMinimum($field, $amount);
        }
    }

    private function assertCurrency(string $currency): void
    {
        if (strtoupper($currency) !== 'IQD') {
            throw WaylException::unsupportedCurrency($currency);
        }
    }

    private function assertUrl(string $url, string $field): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw WaylException::invalidUrl($field);
        }
    }

    private function assertWebhookSecret(string $secret): void
    {
        $length = mb_strlen($secret);

        if ($length < 10 || $length > 255) {
            throw WaylException::invalidWebhookSecretLength($length);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
