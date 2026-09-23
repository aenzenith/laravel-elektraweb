<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class CancellationPenalty
{
    public function __construct(
        public readonly ?string $description,
        public readonly ?int $periodInDays,
        public readonly ?float $penaltyPercentage,
        public readonly bool $isRefundable,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            description: $r->nullableString('description'),
            periodInDays: $r->nullableInt('period-in-days'),
            penaltyPercentage: $r->nullableFloat('penalty-percentage'),
            isRefundable: $r->bool('is-refundable'),
        );
    }
}
