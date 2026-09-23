<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

use Throwable;

/**
 * The provider could not be reached at transport level (DNS, TLS, timeout...).
 */
final class ConnectionFailedException extends BookingApiException
{
    public static function wrap(string $method, string $uri, Throwable $previous): self
    {
        return new self(
            sprintf('Could not reach ElektraWeb Booking API (%s %s): %s', $method, $uri, $previous->getMessage()),
            0,
            $previous
        );
    }
}
