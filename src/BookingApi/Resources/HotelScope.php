<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Aenzenith\ElektraWeb\BookingApi\Cache\ReferenceCache;
use Aenzenith\ElektraWeb\BookingApi\Http\Connector;

/**
 * Everything you can do with one hotel.
 *
 *     $hotel = BookingApi::hotel(26780);
 *     $hotel->definitions()->all();
 *     $hotel->rates()->search(PriceSearch::make('2026-07-01', '2026-07-05', 2));
 *     $hotel->reservations()->create($reservation);
 */
final class HotelScope
{
    public function __construct(
        private readonly Connector $connector,
        private readonly ReferenceCache $cache,
        private readonly BookingApiConfig $config,
        private readonly int $hotelId,
    ) {}

    public function id(): int
    {
        return $this->hotelId;
    }

    public function definitions(): DefinitionsResource
    {
        return new DefinitionsResource($this->connector, $this->cache, $this->config, $this->hotelId);
    }

    public function rates(): RatesResource
    {
        return new RatesResource($this->connector, $this->cache, $this->config, $this->hotelId);
    }

    public function reservations(): ReservationsResource
    {
        return new ReservationsResource($this->connector, $this->cache, $this->config, $this->hotelId);
    }

    public function guestCancellation(): GuestCancellationResource
    {
        return new GuestCancellationResource($this->connector, $this->cache, $this->config, $this->hotelId);
    }
}
