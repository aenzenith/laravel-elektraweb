<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Data\Offer;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Requests\Concerns\HasReservationDetails;
use Aenzenith\ElektraWeb\Support\Payload;
use DateTimeInterface;

/**
 * Body for POST /hotel/{id}/updateReservation
 */
final class UpdateReservation
{
    use HasReservationDetails;

    private ?float $agencyCommission = null;

    private ?string $promoCode = null;

    private ?bool $useGuestBonus = null;

    private ?bool $isOffer = null;

    private function __construct(
        private readonly int $reservationId,
    ) {
        if ($reservationId < 1) {
            throw InvalidRequestException::because('Reservation id must be a positive integer.');
        }
    }

    public static function for(int $reservationId): self
    {
        return new self($reservationId);
    }

    public static function fromOffer(int $reservationId, Offer $offer, DateTimeInterface|string $checkIn, DateTimeInterface|string $checkOut, string $nationality): self
    {
        return (new self($reservationId))
            ->forOffer($offer)
            ->withStay($checkIn, $checkOut)
            ->withNationality($nationality);
    }

    public function reservationId(): int
    {
        return $this->reservationId;
    }

    public function withAgencyCommission(?float $percent): self
    {
        $copy = clone $this;
        $copy->agencyCommission = $percent;

        return $copy;
    }

    public function withPromoCode(?string $promoCode): self
    {
        $copy = clone $this;
        $copy->promoCode = $promoCode !== null && trim($promoCode) !== '' ? trim($promoCode) : null;

        return $copy;
    }

    public function useGuestBonus(?bool $useGuestBonus = true): self
    {
        $copy = clone $this;
        $copy->useGuestBonus = $useGuestBonus;

        return $copy;
    }

    public function asOffer(?bool $isOffer = true): self
    {
        $copy = clone $this;
        $copy->isOffer = $isOffer;

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(int $hotelId): array
    {
        return array_replace(
            ['hotel-id' => $hotelId, 'reservation-id' => $this->reservationId],
            $this->sharedPayload($hotelId),
            Payload::withoutNulls([
                'agency-commission' => $this->agencyCommission,
                'promo-code' => $this->promoCode,
                'use-guest-bonus' => $this->useGuestBonus,
                'is-offer' => $this->isOffer,
            ]),
        );
    }
}
