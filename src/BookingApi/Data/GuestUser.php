<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /hotel/{id}/find-guest-user. Docs ship no example; lenient field lookup.
 */
final class GuestUser
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $email,
        public readonly ?string $name,
        public readonly ?string $surname,
        public readonly ?string $phone,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->nullableInt('id') ?? $r->nullableInt('guest-id') ?? $r->nullableInt('user-id'),
            email: $r->nullableString('email'),
            name: $r->nullableString('name') ?? $r->nullableString('first-name'),
            surname: $r->nullableString('surname') ?? $r->nullableString('last-name'),
            phone: $r->nullableString('phone'),
            raw: $data,
        );
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return ArrayReader::of($this->raw)->get($path, $default);
    }
}
