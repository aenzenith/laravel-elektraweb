<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\DailyAvailability;
use Aenzenith\ElektraWeb\BookingApi\Data\OfferCollection;
use Aenzenith\ElektraWeb\BookingApi\Requests\AvailabilitySearch;
use Aenzenith\ElektraWeb\BookingApi\Requests\PriceSearch;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * "Rate & Availability" section. Never cached: prices change by the minute.
 */
final class RatesResource extends HotelResource
{
    /**
     * GET /hotel/{id}/price/ — bookable offers for a stay.
     *
     * An empty list (or `{"success": false}`) means nothing is available.
     */
    public function search(PriceSearch $search): OfferCollection
    {
        $uri = $this->uri('price/');
        $response = $this->connector->get($uri, $search->toQuery($this->language(), $this->currency()));

        if ($response->successFlag() === false || $response->json() === null) {
            return new OfferCollection;
        }

        return OfferCollection::fromRows($this->expectList($response, $uri));
    }

    /**
     * Same as search() with `onlybestoffer=true`.
     */
    public function bestOffers(PriceSearch $search): OfferCollection
    {
        return $this->search($search->onlyBestOffer());
    }

    /**
     * GET /hotel/{id}/availability — per-day occupancy prices for every rate.
     *
     * @return Collection<int, DailyAvailability>
     */
    public function availability(AvailabilitySearch|DateTimeInterface|string $from, DateTimeInterface|string|null $to = null): Collection
    {
        $search = $from instanceof AvailabilitySearch
            ? $from
            : AvailabilitySearch::between($from, $to ?? $from);

        $uri = $this->uri('availability');
        $response = $this->connector->get($uri, $search->toQuery());

        if ($response->successFlag() === false) {
            return new Collection;
        }

        return (new Collection($this->expectList($response, $uri)))->map(DailyAvailability::fromArray(...));
    }
}
