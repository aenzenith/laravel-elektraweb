<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Events;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\BookingApiException;

/**
 * Fired right before a Booking API exception is thrown to the caller.
 * Listen to this to centralise logging without wrapping every call.
 */
final class RequestFailed
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly BookingApiException $exception,
    ) {}
}
