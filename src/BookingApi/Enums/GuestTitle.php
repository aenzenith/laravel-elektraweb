<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

/**
 * Guest-list "title-id" values.
 */
enum GuestTitle: int
{
    case Mr = 0;
    case Ms = 1;
    case Child = 2;
    case Baby = 3;

    public function isAdult(): bool
    {
        return $this === self::Mr || $this === self::Ms;
    }

    /**
     * The API requires a birthday for children and babies.
     */
    public function requiresBirthday(): bool
    {
        return ! $this->isAdult();
    }
}
