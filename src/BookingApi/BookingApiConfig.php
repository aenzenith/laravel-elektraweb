<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi;

use Aenzenith\ElektraWeb\BookingApi\Auth\ApiKeyCredentials;
use Aenzenith\ElektraWeb\BookingApi\Auth\Credentials;
use Aenzenith\ElektraWeb\BookingApi\Auth\HotelUserCredentials;
use Aenzenith\ElektraWeb\BookingApi\Auth\LoginTokenCredentials;
use Aenzenith\ElektraWeb\BookingApi\Enums\AuthDriver;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\NotConfiguredException;
use InvalidArgumentException;

/**
 * Immutable, validated view of the `elektraweb.booking_api` config array.
 */
final class BookingApiConfig
{
    public const DEFAULT_BASE_URL = 'https://bookingapi.elektraweb.com/';

    /**
     * @param  array<string, mixed>  $authOptions
     * @param  array<string, mixed>  $httpOptions
     * @param  array<string, int>  $cacheTtlMinutes
     */
    public function __construct(
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly ?string $hotelId = null,
        public readonly string $language = 'EN',
        public readonly ?string $currency = null,
        public readonly AuthDriver $authDriver = AuthDriver::ApiKey,
        public readonly array $authOptions = [],
        public readonly ?string $tokenStore = null,
        public readonly int $tokenTtlMinutes = 55,
        public readonly string $tokenPrefix = 'elektraweb:booking:token',
        public readonly int $timeout = 30,
        public readonly int $connectTimeout = 10,
        public readonly int $retryTimes = 2,
        public readonly int $retrySleepMs = 250,
        public readonly array $httpOptions = [],
        public readonly bool $cacheEnabled = true,
        public readonly ?string $cacheStore = null,
        public readonly string $cachePrefix = 'elektraweb:booking',
        public readonly int $cacheStaleMinutes = 4320,
        public readonly array $cacheTtlMinutes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $auth = self::arrayOf($config, 'auth');
        $token = self::arrayOf($config, 'token');
        $http = self::arrayOf($config, 'http');
        $retry = self::arrayOf($http, 'retry');
        $cache = self::arrayOf($config, 'cache');

        $driver = AuthDriver::tryFrom(strtolower(trim((string) ($auth['driver'] ?? ''))) ?: AuthDriver::ApiKey->value);

        if ($driver === null) {
            throw new InvalidArgumentException(sprintf(
                'Unknown ElektraWeb Booking API auth driver [%s]. Expected one of: %s.',
                (string) $auth['driver'],
                implode(', ', array_map(static fn (AuthDriver $case) => $case->value, AuthDriver::cases()))
            ));
        }

        $hotelId = self::stringOrNull($config['hotel_id'] ?? null);
        $currency = self::stringOrNull($config['currency'] ?? null);

        /** @var array<string, int> $ttl */
        $ttl = array_map('intval', self::arrayOf($cache, 'ttl_minutes'));

        return new self(
            baseUrl: self::normalizeBaseUrl((string) ($config['base_url'] ?? self::DEFAULT_BASE_URL)),
            hotelId: $hotelId,
            language: strtoupper((string) ($config['language'] ?? 'EN')) ?: 'EN',
            currency: $currency !== null ? strtoupper($currency) : null,
            authDriver: $driver,
            authOptions: $auth,
            tokenStore: self::stringOrNull($token['store'] ?? null),
            tokenTtlMinutes: max(1, (int) ($token['ttl_minutes'] ?? 55)),
            tokenPrefix: (string) ($token['prefix'] ?? 'elektraweb:booking:token'),
            timeout: max(1, (int) ($http['timeout'] ?? 30)),
            connectTimeout: max(1, (int) ($http['connect_timeout'] ?? 10)),
            retryTimes: max(0, (int) ($retry['times'] ?? 2)),
            retrySleepMs: max(0, (int) ($retry['sleep_ms'] ?? 250)),
            httpOptions: self::arrayOf($http, 'options'),
            cacheEnabled: (bool) ($cache['enabled'] ?? true),
            cacheStore: self::stringOrNull($cache['store'] ?? null),
            cachePrefix: (string) ($cache['prefix'] ?? 'elektraweb:booking'),
            cacheStaleMinutes: max(0, (int) ($cache['stale_minutes'] ?? 4320)),
            cacheTtlMinutes: $ttl,
        );
    }

    /**
     * Build the credentials object for the configured driver.
     *
     * @throws NotConfiguredException when a required secret is empty
     */
    public function credentials(): ?Credentials
    {
        return match ($this->authDriver) {
            AuthDriver::ApiKey => new ApiKeyCredentials($this->requireAuthOption('api_key')),
            AuthDriver::HotelUser => new HotelUserCredentials(
                $this->hotelId ?? throw NotConfiguredException::missing('hotel_id'),
                $this->requireAuthOption('usercode'),
                $this->requireAuthOption('password'),
            ),
            AuthDriver::LoginToken => new LoginTokenCredentials($this->requireAuthOption('login_token')),
            AuthDriver::Captcha => null,
        };
    }

    /**
     * Cache TTL for a reference-data bucket, in minutes. Zero disables caching for that bucket.
     */
    public function cacheTtl(string $bucket): int
    {
        return max(0, (int) ($this->cacheTtlMinutes[$bucket] ?? 0));
    }

    private function requireAuthOption(string $key): string
    {
        $value = self::stringOrNull($this->authOptions[$key] ?? null);

        return $value ?? throw NotConfiguredException::missing('auth.'.$key);
    }

    private static function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            $url = self::DEFAULT_BASE_URL;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/').'/';
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private static function arrayOf(array $source, string $key): array
    {
        $value = $source[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
