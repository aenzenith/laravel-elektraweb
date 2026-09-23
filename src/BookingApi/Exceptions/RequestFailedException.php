<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

use Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse;

/**
 * The provider answered with a non-2xx status or an explicit failure body.
 */
class RequestFailedException extends BookingApiException
{
    final public function __construct(
        string $message,
        public readonly string $method,
        public readonly string $uri,
        public readonly ApiResponse $response,
    ) {
        parent::__construct($message, $response->status());
    }

    public static function fromResponse(string $method, string $uri, ApiResponse $response): static
    {
        $providerMessage = $response->message();

        $message = sprintf(
            'ElektraWeb Booking API request failed (%s %s) with status %d%s',
            $method,
            $uri,
            $response->status(),
            $providerMessage !== null ? ': '.$providerMessage : '.'
        );

        return new static($message, $method, $uri, $response);
    }

    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Human readable message returned by the provider, if any.
     */
    public function providerMessage(): ?string
    {
        return $this->response->message();
    }

    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function isServerError(): bool
    {
        return $this->status() >= 500;
    }
}
