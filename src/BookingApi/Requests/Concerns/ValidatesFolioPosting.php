<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests\Concerns;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;

/**
 * Fields shared by folio postings: reservation, department, amount in the posting currency and
 * the same amount in the hotel's local currency.
 *
 * @internal
 */
trait ValidatesFolioPosting
{
    private static function positiveId(int $id, string $label): int
    {
        if ($id < 1) {
            throw InvalidRequestException::because($label.' must be a positive integer.');
        }

        return $id;
    }

    private static function currencyCode(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw InvalidRequestException::because('Folio currency must be a three-letter ISO 4217 code.');
        }

        return $currency;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function amounts(float $amount, float $localAmount): array
    {
        $amount = round($amount, 2);
        $localAmount = round($localAmount, 2);

        if (! is_finite($amount) || ! is_finite($localAmount) || $amount == 0.0 || $localAmount == 0.0) {
            throw InvalidRequestException::because('Folio amount and local amount must be non-zero numbers.');
        }

        if (($amount > 0) !== ($localAmount > 0)) {
            throw InvalidRequestException::because('Folio amount and local amount must have the same sign.');
        }

        return [$amount, $localAmount];
    }

    /**
     * @return array<string, mixed>
     */
    private function postingPayload(): array
    {
        return [
            'reservation-id' => $this->reservationId,
            'local-amount' => $this->localAmount,
            'amount' => $this->amount,
            'currency-code' => $this->currency,
            'department-id' => $this->departmentId,
        ];
    }
}
