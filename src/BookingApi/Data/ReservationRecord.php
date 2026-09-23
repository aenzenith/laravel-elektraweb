<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * A reservation row from the B2E list endpoints (reservation-list, -departure, -in-house).
 * The docs ship no example, so common fields are looked up leniently and the raw row is kept.
 */
final class ReservationRecord
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $reservationId,
        public readonly ?string $voucherNo,
        public readonly ?string $guestNames,
        public readonly ?string $checkIn,
        public readonly ?string $checkOut,
        public readonly ?string $status,
        public readonly ?float $totalPrice,
        public readonly ?string $currency,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            reservationId: $r->nullableInt('reservation-id') ?? $r->nullableInt('res-id') ?? $r->nullableInt('res-no') ?? $r->nullableInt('id'),
            voucherNo: $r->nullableString('voucher-no'),
            guestNames: $r->nullableString('guest-names') ?? $r->nullableString('guest-name'),
            checkIn: $r->nullableString('check-in') ?? $r->nullableString('check-in-date'),
            checkOut: $r->nullableString('check-out') ?? $r->nullableString('check-out-date'),
            status: $r->nullableString('reservation-status') ?? $r->nullableString('status'),
            totalPrice: $r->nullableFloat('total-price'),
            currency: $r->nullableString('currency-code') ?? $r->nullableString('currency'),
            raw: $data,
        );
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return ArrayReader::of($this->raw)->get($path, $default);
    }
}
