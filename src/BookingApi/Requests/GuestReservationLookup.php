<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;

/**
 * Body for POST /hotel/{id}/findGuestReservation (SMS cancellation, step 1).
 */
final class GuestReservationLookup
{
    private ?string $surname = null;

    private ?string $voucherNo = null;

    private ?string $phoneNumber = null;

    private ?int $reservationNo = null;

    private function __construct(
        private readonly string $checkIn,
    ) {}

    public static function forCheckIn(DateTimeInterface|string $checkIn): self
    {
        return new self(Dates::toApi($checkIn));
    }

    public function withSurname(?string $surname): self
    {
        $copy = clone $this;
        $copy->surname = $surname !== null && trim($surname) !== '' ? trim($surname) : null;

        return $copy;
    }

    public function withVoucherNo(?string $voucherNo): self
    {
        $copy = clone $this;
        $copy->voucherNo = $voucherNo !== null && trim($voucherNo) !== '' ? trim($voucherNo) : null;

        return $copy;
    }

    public function withPhoneNumber(?string $phoneNumber): self
    {
        $copy = clone $this;
        $copy->phoneNumber = Phone::normalize($phoneNumber);

        return $copy;
    }

    /**
     * Pick one reservation after a lookup returned several candidates.
     */
    public function withReservationNo(?int $reservationNo): self
    {
        $copy = clone $this;
        $copy->reservationNo = $reservationNo;

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        if ($this->surname === null && $this->voucherNo === null && $this->phoneNumber === null && $this->reservationNo === null) {
            throw InvalidRequestException::because('Guest reservation lookup needs a surname, voucher number, phone number or reservation number.');
        }

        return [
            'check-in' => $this->checkIn,
            'surname' => $this->surname ?? '',
            'voucher-no' => $this->voucherNo ?? '',
            'phone-number' => $this->phoneNumber ?? '',
            'reservation-no' => $this->reservationNo,
        ];
    }
}
