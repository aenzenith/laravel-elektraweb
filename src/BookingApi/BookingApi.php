<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi;

use Aenzenith\ElektraWeb\BookingApi\Auth\AccessToken;
use Aenzenith\ElektraWeb\BookingApi\Auth\Authenticator;
use Aenzenith\ElektraWeb\BookingApi\Auth\CacheTokenStore;
use Aenzenith\ElektraWeb\BookingApi\Auth\Credentials;
use Aenzenith\ElektraWeb\BookingApi\Auth\TokenStore;
use Aenzenith\ElektraWeb\BookingApi\Cache\ReferenceCache;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\NotConfiguredException;
use Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse;
use Aenzenith\ElektraWeb\BookingApi\Http\Connector;
use Aenzenith\ElektraWeb\BookingApi\Http\RequestOptions;
use Aenzenith\ElektraWeb\BookingApi\Http\Transport;
use Aenzenith\ElektraWeb\BookingApi\Resources\ConstantsResource;
use Aenzenith\ElektraWeb\BookingApi\Resources\HotelScope;
use Aenzenith\ElektraWeb\Contracts\Module;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * ElektraWeb Booking API module.
 *
 * Immutable: `with*` methods return a configured copy, so per-request tweaks
 * (captcha token, language, different credentials) never leak between callers.
 */
class BookingApi implements Module
{
    private RequestOptions $options;

    private ?Connector $connector = null;

    private ?ReferenceCache $referenceCache = null;

    private ?TokenStore $tokenStore = null;

    public function __construct(
        private readonly BookingApiConfig $config,
        private readonly HttpFactory $http,
        private readonly ?CacheFactory $cacheFactory = null,
        private readonly ?Dispatcher $events = null,
    ) {
        $this->options = new RequestOptions(
            credentials: $this->resolveConfiguredCredentials(),
            language: $config->language,
            currency: $config->currency,
            cache: $config->cacheEnabled,
        );
    }

    public static function name(): string
    {
        return 'booking_api';
    }

    public function config(): BookingApiConfig
    {
        return $this->config;
    }

    public function options(): RequestOptions
    {
        return $this->options;
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent per-call configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Authenticate with different credentials (e.g. another hotel user).
     */
    public function withCredentials(?Credentials $credentials): static
    {
        return $this->withOptions($this->options->withCredentials($credentials));
    }

    /**
     * Send a Google reCAPTCHA token in the `x-captcha` header.
     */
    public function withCaptcha(?string $token): static
    {
        return $this->withOptions($this->options->withCaptcha($token));
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function withHeaders(array $headers): static
    {
        return $this->withOptions($this->options->withHeaders($headers));
    }

    public function withLanguage(string $language): static
    {
        return $this->withOptions($this->options->withLanguage($language));
    }

    public function withCurrency(?string $currency): static
    {
        return $this->withOptions($this->options->withCurrency($currency));
    }

    /**
     * Bypass the reference-data cache for this instance.
     */
    public function withoutCache(): static
    {
        return $this->withOptions($this->options->withCache(false));
    }

    /**
     * Swap the token store (defaults to the configured cache store).
     */
    public function withTokenStore(TokenStore $store): static
    {
        $copy = clone $this;
        $copy->tokenStore = $store;
        $copy->connector = null;

        return $copy;
    }

    /*
    |--------------------------------------------------------------------------
    | Entry points
    |--------------------------------------------------------------------------
    */

    /**
     * Scope calls to a hotel. Falls back to the configured default hotel.
     */
    public function hotel(int|string|null $hotelId = null): HotelScope
    {
        $id = $hotelId ?? $this->config->hotelId ?? throw NotConfiguredException::hotelId();

        if (! is_numeric($id) || (int) $id < 1) {
            throw NotConfiguredException::hotelId();
        }

        return new HotelScope($this->connector(), $this->referenceCache(), $this->config, (int) $id);
    }

    /**
     * Countries, cities and standard board types.
     */
    public function constants(): ConstantsResource
    {
        return new ConstantsResource($this->connector(), $this->referenceCache(), $this->config);
    }

    /**
     * Force a POST /login and return the token (also cached for later calls).
     */
    public function login(): AccessToken
    {
        $credentials = $this->options->credentials ?? throw NotConfiguredException::missing('auth');

        return $this->connector()->authenticator()->login($credentials);
    }

    /**
     * Current token, logging in when needed.
     */
    public function token(): AccessToken
    {
        $credentials = $this->options->credentials ?? throw NotConfiguredException::missing('auth');

        return $this->connector()->authenticator()->token($credentials);
    }

    /**
     * Drop the cached JWT so the next call logs in again.
     */
    public function forgetToken(): void
    {
        $this->connector()->forgetToken();
    }

    /**
     * GET /health-check — no authentication.
     */
    public function healthCheck(): ApiResponse
    {
        return $this->connector()->request('GET', 'health-check', authenticate: false, throw: false);
    }

    /**
     * Low-level access for endpoints this SDK does not model yet.
     */
    public function connector(): Connector
    {
        return $this->connector ??= new Connector(
            $this->transport(),
            new Authenticator($this->transport(), $this->tokenStore(), $this->config, $this->events),
            $this->options,
            $this->events,
        );
    }

    public function cache(): ReferenceCache
    {
        return $this->referenceCache();
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function withOptions(RequestOptions $options): static
    {
        $copy = clone $this;
        $copy->options = $options;
        $copy->connector = null;

        return $copy;
    }

    private function transport(): Transport
    {
        return new Transport($this->http, $this->config);
    }

    private function tokenStore(): TokenStore
    {
        return $this->tokenStore ??= new CacheTokenStore(
            $this->cacheRepository($this->config->tokenStore) ?? throw NotConfiguredException::missing('token.store'),
        );
    }

    private function referenceCache(): ReferenceCache
    {
        return $this->referenceCache ??= new ReferenceCache(
            $this->cacheRepository($this->config->cacheStore),
            $this->config->cachePrefix,
            $this->config->cacheStaleMinutes,
            $this->config->cacheEnabled,
            $this->events,
        );
    }

    private function cacheRepository(?string $store): ?Repository
    {
        if ($this->cacheFactory === null) {
            return null;
        }

        return $this->cacheFactory->store($store);
    }

    /**
     * Missing secrets are reported lazily on the first authenticated call, so
     * instantiating the module (e.g. in a container) never throws.
     */
    private function resolveConfiguredCredentials(): ?Credentials
    {
        try {
            return $this->config->credentials();
        } catch (Throwable) {
            return null;
        }
    }
}
