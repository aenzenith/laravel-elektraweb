<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Exceptions;

use Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse;

/**
 * POST /login was rejected or did not return a usable token.
 */
final class AuthenticationException extends RequestFailedException
{
    public static function noToken(ApiResponse $response): self
    {
        $providerMessage = $response->message();

        return new self(
            'ElektraWeb Booking API login did not return a token'.($providerMessage !== null ? ': '.$providerMessage : '.'),
            'POST',
            'login',
            $response
        );
    }
}
