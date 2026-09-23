<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /hotel/{id}/service row. Docs ship no example, field lookups are lenient.
 */
final class ExtraService
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?float $price,
        public readonly ?string $currency,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->nullableInt('service-id') ?? $r->int('id'),
            name: $r->string('service-name', $r->string('name')),
            description: $r->nullableString('description') ?? $r->nullableString('service-description'),
            price: $r->nullableFloat('price') ?? $r->nullableFloat('service-price'),
            currency: $r->nullableString('currency') ?? $r->nullableString('currency-code'),
            raw: $data,
        );
    }
}
