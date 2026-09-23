<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * Persists issued tokens between requests / processes.
 */
interface TokenStore
{
    public function get(string $key): ?AccessToken;

    public function put(string $key, AccessToken $token, int $ttlSeconds): void;

    public function forget(string $key): void;
}
