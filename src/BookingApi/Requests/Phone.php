<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

/**
 * Phone numbers are sent as digits with an optional leading "+".
 */
final class Phone
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        $international = str_starts_with($phone, '+') || str_starts_with($phone, '00');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return null;
        }

        return $international ? '+'.$digits : $digits;
    }
}
