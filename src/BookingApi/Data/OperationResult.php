<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * Generic `{ success, message, ... }` answer used by update / cancel endpoints.
 */
final class OperationResult
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            success: $r->bool('success', true),
            message: $r->nullableString('message'),
            raw: $data,
        );
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return ArrayReader::of($this->raw)->get($path, $default);
    }
}
