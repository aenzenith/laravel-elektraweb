<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Requests\Concerns\ValidatesFolioPosting;

/**
 * Body for POST /hotel/{id}/folio/add-revenue: a charge (revenue) written to the reservation's folio.
 *
 *     FolioRevenue::make(
 *         reservationId: 95989318,
 *         departmentId: 123,      // selling department, e.g. "Restaurant"
 *         revenueId: 456,         // revenue item, e.g. "Beverage"
 *         amount: 1000.00,
 *         currency: 'TRY',
 *         localAmount: 1000.00,
 *     );
 */
final class FolioRevenue
{
    use ValidatesFolioPosting;

    private function __construct(
        public readonly int $reservationId,
        public readonly int $departmentId,
        public readonly int $revenueId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly float $localAmount,
    ) {}

    public static function make(int $reservationId, int $departmentId, int $revenueId, float $amount, string $currency, float $localAmount): self
    {
        [$amount, $localAmount] = self::amounts($amount, $localAmount);

        return new self(
            self::positiveId($reservationId, 'Reservation id'),
            self::positiveId($departmentId, 'Folio department id'),
            self::positiveId($revenueId, 'Folio revenue id'),
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
        return $this->postingPayload() + ['revenue-id' => $this->revenueId];
    }
}
