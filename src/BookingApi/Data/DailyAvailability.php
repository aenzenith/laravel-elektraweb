<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * One row of GET /hotel/{id}/availability: per-day, per-rate occupancy prices.
 */
final class DailyAvailability
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly string $date,
        public readonly int $hotelId,
        public readonly ?int $currencyId,
        public readonly int $boardTypeId,
        public readonly int $rateTypeId,
        public readonly int $rateCodeId,
        public readonly int $roomTypeId,
        public readonly ?int $marketId,
        public readonly ?float $single,
        public readonly ?float $double,
        public readonly ?float $triple,
        public readonly ?float $quad,
        public readonly ?float $extraBed,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            date: $r->string('date'),
            hotelId: $r->int('hotel-id'),
            currencyId: $r->nullableInt('currency-id'),
            boardTypeId: $r->int('board-type-id'),
            rateTypeId: $r->int('rate-type-id'),
            rateCodeId: $r->int('rate-code-id'),
            roomTypeId: $r->int('room-type-id'),
            marketId: $r->nullableInt('market-id'),
            single: $r->nullableFloat('sng'),
            double: $r->nullableFloat('dbl'),
            triple: $r->nullableFloat('trp'),
            quad: $r->nullableFloat('quad'),
            extraBed: $r->nullableFloat('extra-bed'),
            raw: $data,
        );
    }

    /**
     * Any occupancy price by its API key (e.g. "younger-chd", "sng-extra-baby").
     */
    public function price(string $key): ?float
    {
        return ArrayReader::of($this->raw)->nullableFloat($key);
    }
}
