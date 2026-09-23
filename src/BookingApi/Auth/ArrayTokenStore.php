<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

/**
 * In-memory store. Handy for tests and for processes that must never share tokens.
 */
final class ArrayTokenStore implements TokenStore
{
    /**
     * @var array<string, AccessToken>
     */
    private array $tokens = [];

    public function get(string $key): ?AccessToken
    {
        return $this->tokens[$key] ?? null;
    }

    public function put(string $key, AccessToken $token, int $ttlSeconds): void
    {
        $this->tokens[$key] = $token;
    }

    public function forget(string $key): void
    {
        unset($this->tokens[$key]);
    }
}
