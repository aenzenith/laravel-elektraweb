<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

final class RoomType
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly ?int $groupId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?string $bedOptions,
        public readonly ?float $area,
        public readonly ?int $level,
        public readonly RoomRules $rules,
        public readonly bool $hasWifi,
        public readonly bool $hasSafe,
        public readonly bool $hasPrivateBath,
        public readonly bool $hasHairdryer,
        public readonly bool $hasBalcony,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            id: $r->int('room-id'),
            groupId: $r->nullableInt('room-group-id'),
            name: $r->string('room-name'),
            code: $r->string('room-code'),
            description: $r->nullableString('room-property'),
            imageUrl: $r->nullableString('room-image-url'),
            bedOptions: $r->nullableString('room-bed-options'),
            area: $r->nullableFloat('room-area'),
            level: $r->nullableInt('room-level'),
            rules: RoomRules::fromArray($r->array('room-rules')),
            hasWifi: $r->bool('room-has-wifi'),
            hasSafe: $r->bool('room-has-safe'),
            hasPrivateBath: $r->bool('room-has-private-bath'),
            hasHairdryer: $r->bool('room-has-hairdryer'),
            hasBalcony: $r->bool('room-has-balcony'),
            raw: $data,
        );
    }

    /**
     * Description with HTML stripped and whitespace collapsed.
     */
    public function plainDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : $text;
    }
}
