<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

/**
 * The provider returned 2xx but the body does not have the documented shape.
 */
final class InvalidResponseException extends BookingApiException
{
    public static function expectedList(string $uri): self
    {
        return new self(sprintf('ElektraWeb Booking API [%s] was expected to return a JSON list.', $uri));
    }

    public static function expectedObject(string $uri): self
    {
        return new self(sprintf('ElektraWeb Booking API [%s] was expected to return a JSON object.', $uri));
    }
}
