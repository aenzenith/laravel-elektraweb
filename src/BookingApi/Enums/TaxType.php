<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

/**
 * createReservation "tax-type".
 */
enum TaxType: int
{
    case Company = 1;
    case Personal = 2;
}
