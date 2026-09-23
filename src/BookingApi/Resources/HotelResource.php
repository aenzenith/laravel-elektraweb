<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Aenzenith\ElektraWeb\BookingApi\Cache\ReferenceCache;
use Aenzenith\ElektraWeb\BookingApi\Http\Connector;

/**
 * Endpoint group scoped to one hotel.
 */
abstract class HotelResource extends Resource
{
    public function __construct(
        Connector $connector,
        ReferenceCache $cache,
        BookingApiConfig $config,
        protected readonly int $hotelId,
    ) {
        parent::__construct($connector, $cache, $config);
    }

    public function hotelId(): int
    {
        return $this->hotelId;
    }

    protected function uri(string $path): string
    {
        return sprintf('hotel/%d/%s', $this->hotelId, ltrim($path, '/'));
    }

    protected function cacheScope(): string
    {
        return 'hotel:'.$this->hotelId;
    }

    protected function cacheKey(string ...$parts): string
    {
        return $parts === [] ? '-' : implode(':', $parts);
    }
}
