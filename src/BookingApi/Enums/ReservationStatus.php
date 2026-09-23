<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

/**
 * reservation-list "reservation-status" filter keys.
 */
enum ReservationStatus: string
{
    case Reservation = 'Reservation';
    case InHouse = 'InHouse';
    case CheckOut = 'CheckOut';
    case Cancelled = 'Cancelled';
}
