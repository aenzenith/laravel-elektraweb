<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests\Concerns;

use Aenzenith\ElektraWeb\BookingApi\Data\Offer;
use Aenzenith\ElektraWeb\BookingApi\Enums\PaymentType;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Requests\Guest;
use Aenzenith\ElektraWeb\Support\Dates;
use Aenzenith\ElektraWeb\Support\Payload;
use DateTimeInterface;

/**
 * Fields shared by createReservation and updateReservation.
 *
 * @internal
 */
trait HasReservationDetails
{
    private ?int $rateTypeId = null;

    private ?int $boardTypeId = null;

    private ?int $rateCodeId = null;

    private ?int $roomTypeId = null;

    private ?int $roomId = null;

    private ?string $currencyCode = null;

    private ?float $totalPrice = null;

    private ?int $priceAgencyId = null;

    private ?int $marketId = null;

    private ?string $nationality = null;

    private ?string $checkIn = null;

    private ?string $checkOut = null;

    private ?int $adultCount = null;

    private ?int $elderChildCount = null;

    private ?int $youngerChildCount = null;

    private ?int $babyCount = null;

    private ?int $roomCount = null;

    /**
     * @var list<Guest>
     */
    private array $guests = [];

    private ?string $voucherNo = null;

    private ?float $sellerCommission = null;

    private ?PaymentType $paymentType = null;

    /**
     * Copy rate identifiers, price and currency from a search result.
     */
    public function forOffer(Offer $offer): static
    {
        $copy = clone $this;
        $copy->rateTypeId = $offer->rateTypeId;
        $copy->boardTypeId = $offer->boardTypeId;
        $copy->rateCodeId = $offer->rateCodeId;
        $copy->roomTypeId = $offer->roomTypeId;
        $copy->priceAgencyId = $offer->priceAgencyId;
        $copy->currencyCode = $offer->currency;
        $copy->totalPrice = $offer->totalPrice();
        $copy->marketId = $offer->marketId;
        $copy->roomCount = $offer->roomCount;

        return $copy;
    }

    public function withRate(int $rateTypeId, int $boardTypeId, int $rateCodeId, int $roomTypeId, int $priceAgencyId): static
    {
        $copy = clone $this;
        $copy->rateTypeId = $rateTypeId;
        $copy->boardTypeId = $boardTypeId;
        $copy->rateCodeId = $rateCodeId;
        $copy->roomTypeId = $roomTypeId;
        $copy->priceAgencyId = $priceAgencyId;

        return $copy;
    }

    public function withPrice(float $totalPrice, string $currencyCode): static
    {
        if ($totalPrice < 0) {
            throw InvalidRequestException::because('Total price cannot be negative.');
        }

        $copy = clone $this;
        $copy->totalPrice = $totalPrice;
        $copy->currencyCode = strtoupper(trim($currencyCode));

        return $copy;
    }

    public function withStay(DateTimeInterface|string $checkIn, DateTimeInterface|string $checkOut): static
    {
        $from = Dates::toApi($checkIn);
        $to = Dates::toApi($checkOut);

        if ($to <= $from) {
            throw InvalidRequestException::because('Check-out must be after check-in.');
        }

        $copy = clone $this;
        $copy->checkIn = $from;
        $copy->checkOut = $to;

        return $copy;
    }

    public function withNationality(string $countryCode2): static
    {
        $copy = clone $this;
        $copy->nationality = strtoupper(trim($countryCode2));

        return $copy;
    }

    /**
     * Explicit occupancy. When omitted, counts are derived from the guest list.
     */
    public function withOccupancy(int $adults, int $elderChildren = 0, int $youngerChildren = 0, int $babies = 0): static
    {
        if ($adults < 1) {
            throw InvalidRequestException::because('At least one adult is required.');
        }

        if (min($elderChildren, $youngerChildren, $babies) < 0) {
            throw InvalidRequestException::because('Occupancy counts cannot be negative.');
        }

        $copy = clone $this;
        $copy->adultCount = $adults;
        $copy->elderChildCount = $elderChildren;
        $copy->youngerChildCount = $youngerChildren;
        $copy->babyCount = $babies;

        return $copy;
    }

    /**
     * @param  iterable<mixed>  $guests
     */
    public function withGuests(iterable $guests): static
    {
        $list = [];

        foreach ($guests as $guest) {
            if (! $guest instanceof Guest) {
                throw InvalidRequestException::because('Guest list must only contain Guest objects.');
            }

            $list[] = $guest;
        }

        $copy = clone $this;
        $copy->guests = $list;

        return $copy;
    }

    public function addGuest(Guest $guest): static
    {
        $copy = clone $this;
        $copy->guests[] = $guest;

        return $copy;
    }

    public function withRoom(?int $roomId): static
    {
        $copy = clone $this;
        $copy->roomId = $roomId;

        return $copy;
    }

    public function withMarket(?int $marketId): static
    {
        $copy = clone $this;
        $copy->marketId = $marketId;

        return $copy;
    }

    public function withRoomCount(int $roomCount): static
    {
        if ($roomCount < 1) {
            throw InvalidRequestException::because('Room count must be at least 1.');
        }

        $copy = clone $this;
        $copy->roomCount = $roomCount;

        return $copy;
    }

    public function withVoucherNo(?string $voucherNo): static
    {
        $copy = clone $this;
        $copy->voucherNo = $voucherNo !== null && trim($voucherNo) !== '' ? trim($voucherNo) : null;

        return $copy;
    }

    /**
     * Payment method shown on the reservation card; also decides the confirmation e-mail content.
     */
    public function withPaymentType(?PaymentType $paymentType): static
    {
        $copy = clone $this;
        $copy->paymentType = $paymentType;

        return $copy;
    }

    public function paymentType(): ?PaymentType
    {
        return $this->paymentType;
    }

    public function withSellerCommission(?float $percent): static
    {
        $copy = clone $this;
        $copy->sellerCommission = $percent;

        return $copy;
    }

    /**
     * @return list<Guest>
     */
    public function guests(): array
    {
        return $this->guests;
    }

    public function totalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function currencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function checkIn(): ?string
    {
        return $this->checkIn;
    }

    public function checkOut(): ?string
    {
        return $this->checkOut;
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedPayload(int $hotelId): array
    {
        foreach ([
            'rate type' => $this->rateTypeId,
            'board type' => $this->boardTypeId,
            'room type' => $this->roomTypeId,
            'price agency' => $this->priceAgencyId,
            'total price' => $this->totalPrice,
            'currency' => $this->currencyCode,
            'check-in / check-out' => $this->checkIn,
            'nationality' => $this->nationality,
        ] as $label => $value) {
            if ($value === null || $value === '') {
                throw InvalidRequestException::because(sprintf('Reservation is missing its %s.', $label));
            }
        }

        $adults = $this->adultCount ?? $this->countGuests(static fn (Guest $guest): bool => $guest->isAdult());

        if ($adults < 1) {
            throw InvalidRequestException::because('Reservation needs at least one adult (set occupancy or add adult guests).');
        }

        return Payload::withoutNulls([
            'hotel-id' => $hotelId,
            'rate-type-id' => $this->rateTypeId,
            'board-type-id' => $this->boardTypeId,
            'rate-code-id' => $this->rateCodeId,
            'room-type-id' => $this->roomTypeId,
            'room-id' => $this->roomId,
            'currency-code' => $this->currencyCode,
            'total-price' => $this->totalPrice,
            'price-agency-id' => $this->priceAgencyId,
            'market-id' => $this->marketId,
            'adult-count' => $adults,
            'nationality' => $this->nationality,
            'check-in' => $this->checkIn,
            'check-out' => $this->checkOut,
            'elder-child-count' => $this->elderChildCount ?? $this->countGuests(static fn (Guest $guest): bool => $guest->isChild()),
            'younger-child-count' => $this->youngerChildCount ?? 0,
            'baby-count' => $this->babyCount ?? $this->countGuests(static fn (Guest $guest): bool => $guest->isBaby()),
            'guest-list' => $this->guests !== [] ? array_map(static fn (Guest $guest): array => $guest->toPayload(), $this->guests) : null,
            'room-count' => $this->roomCount,
            'voucher-no' => $this->voucherNo,
            'seller-commission' => $this->sellerCommission,
            'payment-type' => $this->paymentType?->value,
        ]);
    }

    /**
     * @param  callable(Guest): bool  $predicate
     */
    private function countGuests(callable $predicate): int
    {
        return count(array_filter($this->guests, $predicate));
    }
}
