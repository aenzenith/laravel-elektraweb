<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

use Illuminate\Contracts\Cache\Repository;

final class CacheTokenStore implements TokenStore
{
    public function __construct(
        private readonly Repository $cache,
    ) {}

    public function get(string $key): ?AccessToken
    {
        $cached = $this->cache->get($key);

        if (! is_array($cached)) {
            return null;
        }

        /** @var array{value?: mixed, expires_at?: mixed} $cached */
        return AccessToken::fromArray($cached);
    }

    public function put(string $key, AccessToken $token, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }

        $this->cache->put($key, $token->toArray(), $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }
}
