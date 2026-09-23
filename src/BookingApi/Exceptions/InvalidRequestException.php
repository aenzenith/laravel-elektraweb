<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

/**
 * Thrown before any HTTP call when a request object cannot be sent as-is.
 */
final class InvalidRequestException extends BookingApiException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
