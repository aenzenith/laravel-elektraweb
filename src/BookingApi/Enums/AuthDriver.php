<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

enum AuthDriver: string
{
    case ApiKey = 'api_key';
    case HotelUser = 'hotel_user';
    case LoginToken = 'login_token';
    case Captcha = 'captcha';

    /**
     * Whether this driver obtains a JWT through POST /login.
     */
    public function usesLogin(): bool
    {
        return $this !== self::Captcha;
    }
}
