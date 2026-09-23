<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class RoomRules
{
    public function __construct(
        public readonly ?int $maxAdults,
        public readonly ?int $maxChildren,
        public readonly ?int $minChildren,
        public readonly ?int $maxBabies,
        public readonly ?int $maxPax,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            maxAdults: $r->nullableInt('max-adult-capacity'),
            maxChildren: $r->nullableInt('max-child-capacity'),
            minChildren: $r->nullableInt('min-child-capacity'),
            maxBabies: $r->nullableInt('max-baby-capacity'),
            maxPax: $r->nullableInt('max-pax-capacity'),
        );
    }
}
