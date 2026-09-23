<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Http;

use Aenzenith\ElektraWeb\BookingApi\Auth\Credentials;

/**
 * Per-call overrides carried by a BookingApi instance. Immutable.
 */
final class RequestOptions
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly ?Credentials $credentials = null,
        public readonly ?string $captcha = null,
        public readonly array $headers = [],
        public readonly string $language = 'EN',
        public readonly ?string $currency = null,
        public readonly bool $cache = true,
    ) {}

    public function withCredentials(?Credentials $credentials): self
    {
        return new self($credentials, $this->captcha, $this->headers, $this->language, $this->currency, $this->cache);
    }

    public function withCaptcha(?string $captcha): self
    {
        return new self($this->credentials, $captcha, $this->headers, $this->language, $this->currency, $this->cache);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self($this->credentials, $this->captcha, array_replace($this->headers, $headers), $this->language, $this->currency, $this->cache);
    }

    public function withLanguage(string $language): self
    {
        return new self($this->credentials, $this->captcha, $this->headers, strtoupper($language), $this->currency, $this->cache);
    }

    public function withCurrency(?string $currency): self
    {
        return new self($this->credentials, $this->captcha, $this->headers, $this->language, $currency !== null ? strtoupper($currency) : null, $this->cache);
    }

    public function withCache(bool $cache): self
    {
        return new self($this->credentials, $this->captcha, $this->headers, $this->language, $this->currency, $cache);
    }

    /**
     * Headers that identify the caller: JWT and/or captcha.
     *
     * @return array<string, string>
     */
    public function identityHeaders(): array
    {
        return $this->captcha !== null ? ['x-captcha' => $this->captcha] : [];
    }
}
