<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Auth;

use Aenzenith\ElektraWeb\BookingApi\BookingApiConfig;
use Aenzenith\ElektraWeb\BookingApi\Events\TokenIssued;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\AuthenticationException;
use Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse;
use Aenzenith\ElektraWeb\BookingApi\Http\Transport;
use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Exchanges credentials for a JWT and keeps it cached until it expires.
 */
final class Authenticator
{
    private const TOKEN_PATHS = [
        'token',
        'access_token',
        'access-token',
        'jwt',
        'jwt_code',
        'jwt-code',
        'data.token',
        'data.access_token',
        'data.jwt',
    ];

    public function __construct(
        private readonly Transport $transport,
        private readonly TokenStore $store,
        private readonly BookingApiConfig $config,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Return a valid token, logging in only when the cached one is missing or expired.
     */
    public function token(Credentials $credentials): AccessToken
    {
        $key = $this->cacheKey($credentials);
        $cached = $this->store->get($key);

        if ($cached !== null && ! $cached->isExpired()) {
            return $cached;
        }

        return $this->login($credentials);
    }

    /**
     * Always call POST /login and cache the result.
     *
     * @throws AuthenticationException
     */
    public function login(Credentials $credentials): AccessToken
    {
        $response = $this->transport->send(
            'POST',
            'login',
            headers: $credentials->headers(),
            json: $credentials->payload(),
        );

        if ($response->failed()) {
            throw new AuthenticationException(
                'ElektraWeb Booking API login was rejected with status '.$response->status()
                    .($response->message() !== null ? ': '.$response->message() : '.'),
                'POST',
                'login',
                $response,
            );
        }

        $raw = $this->extractToken($response) ?? throw AuthenticationException::noToken($response);

        $token = AccessToken::fromString(
            $raw,
            (new DateTimeImmutable)->modify(sprintf('+%d minutes', $this->config->tokenTtlMinutes)),
        );

        $ttl = $token->secondsUntilExpiry() ?? $this->config->tokenTtlMinutes * 60;

        $this->store->put($this->cacheKey($credentials), $token, $ttl);

        $this->events?->dispatch(new TokenIssued($token, $credentials));

        return $token;
    }

    public function forget(Credentials $credentials): void
    {
        $this->store->forget($this->cacheKey($credentials));
    }

    private function cacheKey(Credentials $credentials): string
    {
        return $this->config->tokenPrefix.':'.sha1($this->config->baseUrl.'|'.$credentials->fingerprint());
    }

    private function extractToken(ApiResponse $response): ?string
    {
        $body = $response->json();

        if (is_string($body)) {
            $candidate = trim($body, "\" \n\r\t");

            return AccessToken::looksLikeJwt($candidate) ? $candidate : null;
        }

        if (! is_array($body)) {
            return null;
        }

        foreach (self::TOKEN_PATHS as $path) {
            $value = $response->get($path);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
