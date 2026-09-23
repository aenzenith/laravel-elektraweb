<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\ExchangeRate;
use Aenzenith\ElektraWeb\BookingApi\Data\ExtraService;
use Aenzenith\ElektraWeb\BookingApi\Data\HotelDefinitions;
use Aenzenith\ElektraWeb\BookingApi\Data\HotelParams;
use Aenzenith\ElektraWeb\BookingApi\Requests\ExtraServicesQuery;
use Illuminate\Support\Collection;

/**
 * "Hotel's Definitions" section: reference data that rarely changes.
 */
final class DefinitionsResource extends HotelResource
{
    /**
     * GET /hotel/{id}/hotel-definitions — room types, board types and rate types.
     */
    public function all(?string $language = null): HotelDefinitions
    {
        $language = strtoupper($language ?? $this->language());
        $uri = $this->uri('hotel-definitions');

        return HotelDefinitions::fromArray($this->cachedArray(
            'definitions',
            $this->cacheKey($language),
            fn () => $this->connector->get($uri, ['language' => $language]),
        ));
    }

    /**
     * GET /hotel/{id}/params — booking engine settings, rules, payment flags.
     */
    public function params(int|string|null $roomTypeGroupId = null, ?string $language = null): HotelParams
    {
        $language = strtoupper($language ?? $this->language());
        $group = $roomTypeGroupId !== null ? (string) $roomTypeGroupId : '';
        $uri = $this->uri('params');

        return HotelParams::fromArray($this->cachedArray(
            'params',
            $this->cacheKey($language, $group === '' ? '-' : $group),
            fn () => $this->connector->get($uri, ['room-type-group-id' => $group, 'language' => $language]),
        ));
    }

    /**
     * GET /hotel/{id}/exchange-rate
     *
     * @return Collection<int, ExchangeRate>
     */
    public function exchangeRates(): Collection
    {
        $uri = $this->uri('exchange-rate');

        $rows = $this->cachedArray(
            'exchange_rates',
            $this->cacheKey(),
            fn () => $this->connector->get($uri),
        );

        return (new Collection(array_values(array_filter($rows, 'is_array'))))->map(ExchangeRate::fromArray(...));
    }

    /**
     * GET /hotel/{id}/service — extra services that can be added to a stay.
     *
     * @return Collection<int, ExtraService>
     */
    public function extraServices(?ExtraServicesQuery $query = null): Collection
    {
        $query ??= ExtraServicesQuery::make();
        $params = $query->toQuery($this->language(), $this->currency());
        $uri = $this->uri('service');

        $rows = $this->cachedArray(
            'extra_services',
            $this->cacheKey(sha1(json_encode($params, JSON_THROW_ON_ERROR))),
            fn () => $this->connector->get($uri, $params),
        );

        return (new Collection(array_values(array_filter($rows, 'is_array'))))->map(ExtraService::fromArray(...));
    }
}
