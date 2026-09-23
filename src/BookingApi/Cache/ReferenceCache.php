<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Cache;

use Aenzenith\ElektraWeb\BookingApi\Events\StaleCacheServed;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

/**
 * Read-through cache with a stale fallback, organised in scopes.
 *
 * Fresh entries live for `ttl`; a second copy lives for `staleMinutes` and is
 * served when the resolver throws, so a provider outage does not take the
 * booking engine down with it.
 *
 * Entries belong to a scope (e.g. "hotel:26780", "constants"). Forgetting a
 * scope bumps its generation number, which invalidates every key under it
 * without needing wildcard deletes.
 */
final class ReferenceCache
{
    private const STALE_SUFFIX = ':stale';

    private const GENERATION_PREFIX = 'gen:';

    private const FALLBACK_COOLDOWN_MINUTES = 5;

    public function __construct(
        private readonly ?Repository $cache,
        private readonly string $prefix,
        private readonly int $staleMinutes,
        private readonly bool $enabled = true,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function disabled(): self
    {
        return new self($this->cache, $this->prefix, $this->staleMinutes, false, $this->events);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->cache !== null;
    }

    /**
     * @template T of array
     *
     * @param  Closure(): T  $resolver
     * @return T
     */
    public function remember(string $scope, string $key, int $ttlMinutes, Closure $resolver): array
    {
        if (! $this->isEnabled() || $ttlMinutes <= 0) {
            return $resolver();
        }

        /** @var Repository $cache */
        $cache = $this->cache;
        $fullKey = $this->key($scope, $key);

        $cached = $cache->get($fullKey);

        if (is_array($cached)) {
            /** @var T $cached */
            return $cached;
        }

        try {
            $value = $resolver();
        } catch (Throwable $exception) {
            $stale = $this->staleMinutes > 0 ? $cache->get($fullKey.self::STALE_SUFFIX) : null;

            if (! is_array($stale)) {
                throw $exception;
            }

            $this->events?->dispatch(new StaleCacheServed($fullKey, $exception));

            // Avoid hammering a failing provider: pin the stale copy briefly.
            $cache->put($fullKey, $stale, min($ttlMinutes, self::FALLBACK_COOLDOWN_MINUTES) * 60);

            /** @var T $stale */
            return $stale;
        }

        $cache->put($fullKey, $value, $ttlMinutes * 60);

        if ($this->staleMinutes > 0) {
            $cache->put($fullKey.self::STALE_SUFFIX, $value, max($ttlMinutes, $this->staleMinutes) * 60);
        }

        return $value;
    }

    /**
     * Invalidate every entry of a scope (fresh and stale copies alike).
     */
    public function forget(string $scope): void
    {
        if ($this->cache === null) {
            return;
        }

        $this->cache->forever($this->generationKey($scope), $this->generation($scope) + 1);
    }

    /**
     * Full cache key for an entry, including the scope's current generation.
     */
    public function key(string $scope, string $key): string
    {
        return sprintf('%s:%s:g%d:%s', $this->prefix, $scope, $this->generation($scope), $key);
    }

    private function generation(string $scope): int
    {
        $value = $this->cache?->get($this->generationKey($scope));

        return is_numeric($value) ? (int) $value : 0;
    }

    private function generationKey(string $scope): string
    {
        return $this->prefix.':'.self::GENERATION_PREFIX.$scope;
    }
}
