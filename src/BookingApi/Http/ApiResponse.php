<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Http;

use Aenzenith\ElektraWeb\Support\ArrayReader;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use JsonException;

/**
 * Decoded provider response. Never throws on access, ask it what it holds.
 */
final class ApiResponse
{
    private mixed $decoded;

    private bool $isDecoded = false;

    public function __construct(
        private readonly Response $response,
    ) {}

    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * 2xx and no explicit `"success": false` flag in the body.
     */
    public function successful(): bool
    {
        return $this->response->successful() && $this->successFlag() !== false;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function isHttpError(): bool
    {
        return $this->response->failed();
    }

    public function isUnauthorized(): bool
    {
        return $this->status() === 401;
    }

    /**
     * The body's `success` flag when present.
     */
    public function successFlag(): ?bool
    {
        $body = $this->json();

        if (! is_array($body) || ! array_key_exists('success', $body)) {
            return null;
        }

        return filter_var($body['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Decoded JSON body: array, scalar, or null for an empty body.
     * Non-JSON bodies are returned as the raw string.
     */
    public function json(): mixed
    {
        if ($this->isDecoded) {
            return $this->decoded;
        }

        $this->isDecoded = true;
        $contents = trim($this->body());

        if ($contents === '') {
            return $this->decoded = null;
        }

        try {
            return $this->decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->decoded = $contents;
        }
    }

    /**
     * Body as an associative array, or an empty array when it is not one.
     *
     * @return array<array-key, mixed>
     */
    public function array(): array
    {
        $body = $this->json();

        return is_array($body) ? $body : [];
    }

    public function isList(): bool
    {
        $body = $this->json();

        return is_array($body) && array_is_list($body);
    }

    /**
     * Body as a list of arrays (non-array items are dropped).
     *
     * @return list<array<array-key, mixed>>
     */
    public function list(): array
    {
        return array_values(array_filter($this->array(), 'is_array'));
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->array(), $path, $default);
    }

    public function reader(): ArrayReader
    {
        return ArrayReader::of($this->array());
    }

    public function body(): string
    {
        return (string) $this->response->body();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->response->headers();
    }

    public function header(string $name): ?string
    {
        $value = $this->response->header($name);

        return $value === '' ? null : $value;
    }

    /**
     * Best-effort human readable message from the many shapes the API uses.
     */
    public function message(): ?string
    {
        $body = $this->json();

        if (is_string($body)) {
            return $body !== '' ? $body : null;
        }

        if (! is_array($body)) {
            return null;
        }

        $detailed = self::firstString(Arr::get($body, 'errorDetails'));

        if ($detailed !== null) {
            return $detailed;
        }

        foreach (['message', 'error', 'detail', 'title'] as $key) {
            $value = Arr::get($body, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $errors = Arr::get($body, 'errors');

        if (is_array($errors)) {
            $first = self::firstString($errors);

            if ($first !== null) {
                return $first;
            }
        }

        if (! array_is_list($body)) {
            $first = Arr::first($body, static fn (mixed $value): bool => is_string($value) && trim($value) !== '');

            if (is_string($first)) {
                return trim($first);
            }
        }

        return null;
    }

    public function toIlluminateResponse(): Response
    {
        return $this->response;
    }

    private static function firstString(mixed $value, string $path = ''): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return $path === '' ? trim($value) : $path.': '.trim($value);
        }

        if (! is_array($value)) {
            return null;
        }

        foreach ($value as $key => $item) {
            $nextPath = is_int($key) ? $path : ($path === '' ? (string) $key : $path.'.'.$key);
            $found = self::firstString($item, $nextPath);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
