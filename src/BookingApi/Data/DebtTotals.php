<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /hotel/{id}/reservation-debt-total. Docs ship no example; the raw body is kept.
 */
final class DebtTotals
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return ArrayReader::of($this->raw)->get($path, $default);
    }

    /**
     * Rows of the body when it is a list, otherwise the body itself as a single row.
     *
     * @return list<array<array-key, mixed>>
     */
    public function rows(): array
    {
        if (array_is_list($this->raw)) {
            return array_values(array_filter($this->raw, 'is_array'));
        }

        return [$this->raw];
    }
}
