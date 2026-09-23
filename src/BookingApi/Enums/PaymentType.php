<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

/**
 * createReservation "payment-type".
 */
enum PaymentType: int
{
    case NotPaid = 2;
    case Paid = 3;
}
