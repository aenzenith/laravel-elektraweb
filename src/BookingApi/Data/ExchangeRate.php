<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * One currency of GET /hotel/{id}/exchange-rate.
 *
 * The endpoint answers with a flat object keyed by currency, the hotel's local currency being 1:
 * `{ "rates-date": "2026-10-26T00:00:00.000Z", "EUR": 0.018018, "TRY": 1 }`.
 * `rate` is therefore the amount of this currency one unit of local currency buys.
 */
final class ExchangeRate
{
    private const DATE_KEY = 'rates-date';

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
     * Rates from either the flat object shape or a list of rows.
     *
     * @param  array<array-key, mixed>  $body
     * @return list<self>
     */
    public static function listFrom(array $body): array
    {
        if (array_is_list($body)) {
            return array_map(self::fromArray(...), array_values(array_filter($body, 'is_array')));
        }

        $date = ArrayReader::of($body)->nullableString(self::DATE_KEY);
        $rates = [];

        foreach ($body as $currency => $rate) {
            if ($currency === self::DATE_KEY || ! is_string($currency) || ! is_numeric($rate)) {
                continue;
            }

            $rates[] = new self(strtoupper($currency), (float) $rate, $date, [$currency => $rate, self::DATE_KEY => $date]);
        }

        return $rates;
    }

    /**
     * A row of the list shape.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            currency: strtoupper($r->string('currency', $r->string('currency-code', $r->string('code')))),
            rate: $r->nullableFloat('rate') ?? $r->nullableFloat('exchange-rate') ?? $r->nullableFloat('value'),
            date: $r->nullableString('date') ?? $r->nullableString('rate-date') ?? $r->nullableString(self::DATE_KEY),
            raw: $data,
        );
    }

    /**
     * Convert an amount in this currency to the hotel's local currency. Null without a usable rate.
     */
    public function toLocal(float $amount): ?float
    {
        return $this->rate !== null && $this->rate > 0 ? $amount / $this->rate : null;
    }

    /**
     * Convert an amount in the hotel's local currency to this currency. Null without a usable rate.
     */
    public function fromLocal(float $localAmount): ?float
    {
        return $this->rate !== null && $this->rate > 0 ? $localAmount * $this->rate : null;
    }
}
