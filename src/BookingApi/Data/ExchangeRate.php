<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /hotel/{id}/exchange-rate row. Docs ship no example, field lookups are lenient.
 */
final class ExchangeRate
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly string $currency,
        public readonly ?float $rate,
        public readonly ?string $date,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            currency: strtoupper($r->string('currency', $r->string('currency-code', $r->string('code')))),
            rate: $r->nullableFloat('rate') ?? $r->nullableFloat('exchange-rate') ?? $r->nullableFloat('value'),
            date: $r->nullableString('date') ?? $r->nullableString('rate-date'),
            raw: $data,
        );
    }
}
