<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Enums\ReservationStatus;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;

/**
 * Query for GET /hotel/{id}/reservation-list
 */
final class ReservationListFilter
{
    private ?ReservationStatus $status = null;

    private ?int $reservationId = null;

    private function __construct(
        private readonly string $fromCheckIn,
        private readonly string $toCheckIn,
    ) {}

    public static function checkInBetween(DateTimeInterface|string $from, DateTimeInterface|string $to): self
    {
        $fromDate = Dates::toApi($from);
        $toDate = Dates::toApi($to);

        if ($toDate < $fromDate) {
            throw InvalidRequestException::because('Reservation list range end must not be before its start.');
        }

        return new self($fromDate, $toDate);
    }

    public function withStatus(?ReservationStatus $status): self
    {
        $copy = clone $this;
        $copy->status = $status;

        return $copy;
    }

    public function withReservationId(?int $reservationId): self
    {
        $copy = clone $this;
        $copy->reservationId = $reservationId;

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        $query = [
            'from-check-in' => $this->fromCheckIn,
            'to-check-in' => $this->toCheckIn,
        ];

        if ($this->status !== null) {
            $query['reservation-status'] = $this->status->value;
        }

        if ($this->reservationId !== null) {
            $query['reservation-id'] = $this->reservationId;
        }

        return $query;
    }
}
