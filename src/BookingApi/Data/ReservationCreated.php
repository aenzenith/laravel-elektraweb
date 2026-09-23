<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * POST /hotel/{id}/createReservation success body.
 */
final class ReservationCreated
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $reservationId,
        public readonly ?string $paymentUrl,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        $paymentUrl = null;

        foreach (['payment-url', 'payment_url', 'payment-link', 'payment_link', 'redirect-url', 'redirect_url'] as $key) {
            $paymentUrl = $r->nullableString($key);

            if ($paymentUrl !== null) {
                break;
            }
        }

        return new self(
            reservationId: $r->int('reservation-id'),
            paymentUrl: $paymentUrl,
            raw: $data,
        );
    }
}
