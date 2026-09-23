<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * One row of GET /hotel/{id}/price/
 */
final class Offer
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly string $id,
        public readonly int $hotelId,
        public readonly int $roomTypeId,
        public readonly string $roomTypeName,
        public readonly int $boardTypeId,
        public readonly string $boardTypeName,
        public readonly int $rateTypeId,
        public readonly string $rateTypeName,
        public readonly int $rateCodeId,
        public readonly string $rateCodeName,
        public readonly int $priceAgencyId,
        public readonly ?int $marketId,
        public readonly int $roomCount,
        public readonly int $roomsToSell,
        public readonly bool $stopSell,
        public readonly bool $closedToArrival,
        public readonly bool $closedToDeparture,
        public readonly ?int $minLos,
        public readonly ?int $maxLos,
        public readonly ?int $bookableAfterDays,
        public readonly float $price,
        public readonly ?float $discountedPrice,
        public readonly ?int $currencyId,
        public readonly string $currency,
        public readonly float $commissionPercent,
        public readonly float $discountPercent,
        public readonly float $discountAmount,
        public readonly float $promotionPercent,
        public readonly float $promotionAmount,
        public readonly CancellationPenalty $cancellationPenalty,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->string('id'),
            hotelId: $r->int('hotel-id'),
            roomTypeId: $r->int('room-type-id'),
            roomTypeName: $r->string('room-type'),
            boardTypeId: $r->int('board-type-id'),
            boardTypeName: $r->string('board-type'),
            rateTypeId: $r->int('rate-type-id'),
            rateTypeName: $r->string('rate-type'),
            rateCodeId: $r->int('rate-code-id'),
            rateCodeName: $r->string('rate-code'),
            priceAgencyId: $r->int('price-agency-id'),
            marketId: $r->nullableInt('market-id'),
            roomCount: $r->int('room-count', 1),
            // The API spells this field both ways.
            roomsToSell: $r->nullableInt('room-to-sell') ?? $r->int('room-tosell'),
            stopSell: $r->bool('stop-sell', $r->bool('rate-rules.stop-sell')),
            closedToArrival: $r->bool('stop-sell-closed-to-arrival', $r->bool('rate-rules.stop-sell-closed-to-arrival')),
            closedToDeparture: $r->bool('stop-sell-closed-to-departure', $r->bool('rate-rules.stop-sell-closed-to-departure')),
            minLos: $r->nullableInt('min-los'),
            maxLos: $r->nullableInt('max-los'),
            bookableAfterDays: $r->nullableInt('bookable-after-days'),
            price: $r->float('price'),
            discountedPrice: $r->nullableFloat('discounted-price'),
            currencyId: $r->nullableInt('currency-id'),
            currency: strtoupper($r->string('currency')),
            commissionPercent: $r->float('commission-percent'),
            discountPercent: $r->float('discount-percent'),
            discountAmount: $r->float('discount-amount'),
            promotionPercent: $r->float('promotion-percent'),
            promotionAmount: $r->float('promotion-amount'),
            cancellationPenalty: CancellationPenalty::fromArray($r->array('cancellation-penalty')),
            raw: $data,
        );
    }

    /**
     * The amount the guest actually pays.
     */
    public function totalPrice(): float
    {
        return $this->discountedPrice ?? $this->price;
    }

    public function hasDiscount(): bool
    {
        return $this->discountedPrice !== null && $this->discountedPrice < $this->price;
    }

    /**
     * True when the rate can be booked right now.
     */
    public function isBookable(): bool
    {
        return ! $this->stopSell
            && ! $this->closedToArrival
            && ! $this->closedToDeparture
            && $this->roomsToSell > 0;
    }

    public function isRefundable(): bool
    {
        return $this->cancellationPenalty->isRefundable;
    }

    /**
     * Whether another offer refers to the same rate (same room / board / rate / agency / currency).
     */
    public function matches(self $other): bool
    {
        return $this->roomTypeId === $other->roomTypeId
            && $this->boardTypeId === $other->boardTypeId
            && $this->rateTypeId === $other->rateTypeId
            && $this->rateCodeId === $other->rateCodeId
            && $this->priceAgencyId === $other->priceAgencyId
            && $this->currency === $other->currency
            && $this->roomCount === $other->roomCount
            && ($this->marketId === null || $other->marketId === null || $this->marketId === $other->marketId);
    }
}
