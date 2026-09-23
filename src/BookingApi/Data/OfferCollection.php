<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Offer>
 */
final class OfferCollection extends Collection
{
    /**
     * @param  list<array<array-key, mixed>>  $rows
     */
    public static function fromRows(array $rows): self
    {
        return new self(array_map(Offer::fromArray(...), $rows));
    }

    public function find(string $id): ?Offer
    {
        return $this->first(static fn (Offer $offer): bool => $offer->id === $id);
    }

    public function bookable(): self
    {
        return $this->filter(static fn (Offer $offer): bool => $offer->isBookable())->values();
    }

    public function refundable(): self
    {
        return $this->filter(static fn (Offer $offer): bool => $offer->isRefundable())->values();
    }

    public function forRoomType(int $roomTypeId): self
    {
        return $this->filter(static fn (Offer $offer): bool => $offer->roomTypeId === $roomTypeId)->values();
    }

    public function forBoardType(int $boardTypeId): self
    {
        return $this->filter(static fn (Offer $offer): bool => $offer->boardTypeId === $boardTypeId)->values();
    }

    public function cheapestFirst(): self
    {
        return $this->sortBy(static fn (Offer $offer): float => $offer->totalPrice())->values();
    }

    public function cheapest(): ?Offer
    {
        return $this->cheapestFirst()->first();
    }

    /**
     * Closest match for an offer captured earlier (e.g. from a checkout token)
     * once its id is no longer present in a fresh search.
     */
    public function equivalentTo(Offer $expected): ?Offer
    {
        return $this
            ->filter(static fn (Offer $offer): bool => $offer->matches($expected))
            ->sortBy(static fn (Offer $offer): float => abs($offer->totalPrice() - $expected->totalPrice()))
            ->first();
    }
}
