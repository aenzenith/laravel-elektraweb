<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Enums;

/**
 * Reservation "payment-type": the payment METHOD shown on the ElektraWeb reservation card.
 *
 * It is not a paid / unpaid flag. ElektraWeb has no pre-authorisation or payment-status concept on
 * the Booking API; it records which method the guest uses and tailors the automatic confirmation
 * e-mail to it. Values confirmed by ElektraWeb support (2026-09).
 */
enum PaymentType: int
{
    /** Invoiced to a company account (agency / corporate). */
    case CityLedger = 0;

    /** Cash, typically settled at the hotel. */
    case Cash = 1;

    /** Card payment already taken (e.g. by your own virtual POS). */
    case CreditCard = 2;

    /** Bank transfer. The confirmation e-mail includes the hotel's bank details when they are defined in ElektraWeb. */
    case BankTransfer = 3;

    /** ElektraWeb e-mails the guest a payment link. */
    case PayByLink = 4;

    case Crypto = 5;

    /**
     * Whether ElektraWeb itself will ask the guest to pay (bank details or a payment link in the e-mail).
     */
    public function promptsGuestToPay(): bool
    {
        return $this === self::BankTransfer || $this === self::PayByLink;
    }
}
