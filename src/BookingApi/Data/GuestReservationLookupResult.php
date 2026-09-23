<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;
use Illuminate\Support\Collection;

/**
 * POST /hotel/{id}/findGuestReservation
 *
 * Either an SMS was sent for exactly one reservation ({@see smsSent()}), or
 * several reservations matched and the caller must pick one and retry with
 * its reservation number ({@see needsSelection()}).
 */
final class GuestReservationLookupResult
{
    /**
     * @param  Collection<int, ReservationCandidate>  $candidates
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $smsCheckUid,
        public readonly ?string $message,
        public readonly Collection $candidates,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            success: $r->bool('success'),
            smsCheckUid: $r->nullableString('sms-check-uid'),
            message: $r->nullableString('message'),
            candidates: (new Collection($r->listOfArrays('reservation-list')))->map(ReservationCandidate::fromArray(...)),
            raw: $data,
        );
    }

    public function smsSent(): bool
    {
        return $this->smsCheckUid !== null;
    }

    public function needsSelection(): bool
    {
        return ! $this->smsSent() && $this->candidates->isNotEmpty();
    }
}
