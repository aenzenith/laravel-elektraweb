<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Support;

use Illuminate\Support\Arr;

/**
 * Small, null-safe reader for the kebab-cased arrays the API returns.
 */
final class ArrayReader
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function of(array $data): self
    {
        return new self($data);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->data, $path, $default);
    }

    public function has(string $path): bool
    {
        return Arr::has($this->data, $path);
    }

    public function string(string $path, string $default = ''): string
    {
        $value = $this->get($path);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function nullableString(string $path): ?string
    {
        $value = $this->get($path);

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function int(string $path, int $default = 0): int
    {
        $value = $this->get($path);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function nullableInt(string $path): ?int
    {
        $value = $this->get($path);

        return is_numeric($value) ? (int) $value : null;
    }

    public function float(string $path, float $default = 0.0): float
    {
        $value = $this->get($path);

        return is_numeric($value) ? (float) $value : $default;
    }

    public function nullableFloat(string $path): ?float
    {
        $value = $this->get($path);

        return is_numeric($value) ? (float) $value : null;
    }

    public function bool(string $path, bool $default = false): bool
    {
        $value = $this->get($path);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['true', '1', 'yes'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', '0', 'no', ''], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        return $default;
    }

    public function nullableBool(string $path): ?bool
    {
        $value = $this->get($path);

        return $value === null ? null : $this->bool($path);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(string $path): array
    {
        $value = $this->get($path);

        return is_array($value) ? $value : [];
    }

    /**
     * Items of a nested list that are themselves arrays.
     *
     * @return list<array<array-key, mixed>>
     */
    public function listOfArrays(string $path): array
    {
        return array_values(array_filter($this->array($path), 'is_array'));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
