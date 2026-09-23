<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Resources;

use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Aenzenith\ElektraWeb\BookingApi\Cache\ReferenceCache;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidResponseException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse;
use Aenzenith\ElektraWeb\BookingApi\Http\Connector;
use Closure;

/**
 * Shared plumbing for endpoint groups.
 */
abstract class Resource
{
    public function __construct(
        protected readonly Connector $connector,
        protected readonly ReferenceCache $cache,
        protected readonly BookingApiConfig $config,
    ) {}

    protected function language(): string
    {
        return $this->connector->options()->language;
    }

    protected function currency(): ?string
    {
        return $this->connector->options()->currency ?? $this->config->currency;
    }

    /**
     * Cache scope every entry of this resource lives in.
     */
    abstract protected function cacheScope(): string;

    /**
     * Fetch a cached JSON body, falling back to the provider.
     *
     * @param  Closure(): ApiResponse  $request
     * @return array<array-key, mixed>
     */
    protected function cachedArray(string $bucket, string $key, Closure $request): array
    {
        $cache = $this->connector->options()->cache ? $this->cache : $this->cache->disabled();

        return $cache->remember(
            $this->cacheScope(),
            $bucket.':'.$key,
            $this->config->cacheTtl($bucket),
            static fn (): array => $request()->array(),
        );
    }

    /**
     * Invalidate everything this resource cached.
     */
    public function forget(): void
    {
        $this->cache->forget($this->cacheScope());
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    protected function expectList(ApiResponse $response, string $uri): array
    {
        if ($response->json() === null) {
            return [];
        }

        if (! $response->isList()) {
            throw InvalidResponseException::expectedList($uri);
        }

        return $response->list();
    }

    /**
     * Throw when the body carries `"success": false`.
     */
    protected function ensureSuccess(ApiResponse $response, string $method, string $uri): ApiResponse
    {
        if ($response->successFlag() === false) {
            throw RequestFailedException::fromResponse($method, $uri, $response);
        }

        return $response;
    }
}
