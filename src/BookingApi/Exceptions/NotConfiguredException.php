<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

final class NotConfiguredException extends BookingApiException
{
    public static function missing(string $key): self
    {
        return new self(sprintf(
            'ElektraWeb Booking API is not configured: [elektraweb.booking_api.%s] is empty.',
            $key
        ));
    }

    public static function hotelId(): self
    {
        return new self(
            'No hotel id given. Pass one to hotel($id) or set [elektraweb.booking_api.hotel_id].'
        );
    }
}
