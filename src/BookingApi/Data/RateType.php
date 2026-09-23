<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class RateType
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly ?float $payNowPercent,
        public readonly ?int $minLos,
        public readonly ?int $maxLos,
        public readonly bool $cancellationPossible,
        public readonly ?int $bookableBeforeDays,
        public readonly ?int $daysBeforeArrival,
        public readonly ?int $periodInDays,
        public readonly ?string $paymentInformation,
        public readonly ?string $cancelPolicy,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->int('id'),
            code: $r->string('code'),
            payNowPercent: $r->nullableFloat('pay-now-percent'),
            minLos: $r->nullableInt('min-los'),
            maxLos: $r->nullableInt('max-los'),
            cancellationPossible: $r->bool('cancellation-possible'),
            bookableBeforeDays: $r->nullableInt('bookable-before-days'),
            daysBeforeArrival: $r->nullableInt('days-before-arrival'),
            periodInDays: $r->nullableInt('period-in-days'),
            paymentInformation: $r->nullableString('payment-information'),
            cancelPolicy: $r->nullableString('cancel-policy'),
            raw: $data,
        );
    }

    public function isRefundable(): bool
    {
        return $this->cancellationPossible;
    }
}
