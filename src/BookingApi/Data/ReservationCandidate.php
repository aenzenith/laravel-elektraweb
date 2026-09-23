<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * One entry of findGuestReservation's "reservation-list" when several reservations match.
 */
final class ReservationCandidate
{
    public function __construct(
        public readonly int $reservationNo,
        public readonly string $guestNames,
        public readonly string $checkIn,
        public readonly string $checkOut,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            reservationNo: $r->int('res-no'),
            guestNames: $r->string('guest-names'),
            checkIn: $r->string('check-in-date'),
            checkOut: $r->string('check-out-date'),
        );
    }
}
