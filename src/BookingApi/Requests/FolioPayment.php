<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Requests\Concerns\ValidatesFolioPosting;

/**
 * Body for POST /hotel/{id}/folio/add-payment: a payment collected outside ElektraWeb
 * (e.g. on your own virtual POS) written to the reservation's folio.
 *
 *     FolioPayment::make(
 *         reservationId: 95989318,
 *         departmentId: 789,      // payment department, e.g. "Credit Card"
 *         amount: 250.00,
 *         currency: 'EUR',
 *         localAmount: 13875.00,  // same amount in the hotel's local currency
 *     );
 */
final class FolioPayment
{
    use ValidatesFolioPosting;

    private function __construct(
        public readonly int $reservationId,
        public readonly int $departmentId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly float $localAmount,
    ) {}

    public static function make(int $reservationId, int $departmentId, float $amount, string $currency, float $localAmount): self
    {
        [$amount, $localAmount] = self::amounts($amount, $localAmount);

        return new self(
            self::positiveId($reservationId, 'Reservation id'),
            self::positiveId($departmentId, 'Folio department id'),
            $amount,
            self::currencyCode($currency),
            $localAmount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->postingPayload();
    }
}
