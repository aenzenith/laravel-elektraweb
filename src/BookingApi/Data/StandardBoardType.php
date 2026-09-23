<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /std-board-type row. The public docs ship no example, so field lookups are lenient.
 */
final class StandardBoardType
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->nullableInt('id') ?? $r->nullableInt('std-board-type-id') ?? $r->nullableInt('board-type-id'),
            code: $r->string('code', $r->string('sys-code', $r->string('std-board-type-code'))),
            name: $r->string('name', $r->string('description', $r->string('std-board-type-name'))),
            raw: $data,
        );
    }
}
