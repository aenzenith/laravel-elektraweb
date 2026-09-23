<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\Data\City;
use Aenzenith\ElektraWeb\BookingApi\Data\Country;
use Aenzenith\ElektraWeb\BookingApi\Data\StandardBoardType;
use Illuminate\Support\Collection;

/**
 * "System Constant Lists" — not hotel specific.
 */
final class ConstantsResource extends Resource
{
    /**
     * GET /countries
     *
     * @return Collection<int, Country>
     */
    public function countries(): Collection
    {
        return $this->list('countries')->map(Country::fromArray(...));
    }

    public function country(string $code2): ?Country
    {
        $code2 = strtoupper(trim($code2));

        return $this->countries()->first(static fn (Country $country): bool => $country->code2 === $code2);
    }

    /**
     * GET /cities
     *
     * @return Collection<int, City>
     */
    public function cities(): Collection
    {
        return $this->list('cities')->map(City::fromArray(...));
    }

    /**
     * GET /std-board-type
     *
     * @return Collection<int, StandardBoardType>
     */
    public function standardBoardTypes(): Collection
    {
        return $this->list('std-board-type')->map(StandardBoardType::fromArray(...));
    }

    protected function cacheScope(): string
    {
        return 'constants';
    }

    /**
     * @return Collection<int, array<array-key, mixed>>
     */
    private function list(string $endpoint): Collection
    {
        $rows = $this->cachedArray(
            'constants',
            $endpoint,
            fn () => $this->connector->get($endpoint),
        );

        return new Collection(array_values(array_filter($rows, 'is_array')));
    }
}
