<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class City
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $address,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->int('city-id'),
            address: $r->string('city-address'),
            raw: $data,
        );
    }
}
