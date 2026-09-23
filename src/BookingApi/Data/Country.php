<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class Country
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code2,
        public readonly string $code3,
        public readonly ?string $languageCode,
        public readonly ?string $currencyCode,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->int('country-id'),
            name: $r->string('country-name'),
            code2: strtoupper($r->string('country-code2')),
            code3: strtoupper($r->string('country-code3')),
            languageCode: $r->nullableString('country-language-code2'),
            currencyCode: $r->nullableString('country-currency-code3'),
            raw: $data,
        );
    }
}
