<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;
use Illuminate\Support\Collection;

/**
 * GET /hotel/{id}/hotel-definitions
 */
final class HotelDefinitions
{
    /**
     * @param  Collection<int, RoomType>  $roomTypes
     * @param  Collection<int, BoardType>  $boardTypes
     * @param  Collection<int, RateType>  $rateTypes
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly Collection $roomTypes,
        public readonly Collection $boardTypes,
        public readonly Collection $rateTypes,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $r = ArrayReader::of($data);

        return new self(
            roomTypes: (new Collection($r->listOfArrays('roomtype')))->map(RoomType::fromArray(...)),
            boardTypes: (new Collection($r->listOfArrays('boardtypes')))->map(BoardType::fromArray(...)),
            rateTypes: (new Collection($r->listOfArrays('ratetypes')))->map(RateType::fromArray(...)),
            raw: $data,
        );
    }

    public function roomType(int $id): ?RoomType
    {
        return $this->roomTypes->first(static fn (RoomType $room): bool => $room->id === $id);
    }

    public function boardType(int $id): ?BoardType
    {
        return $this->boardTypes->first(static fn (BoardType $board): bool => $board->id === $id);
    }

    public function rateType(int $id): ?RateType
    {
        return $this->rateTypes->first(static fn (RateType $rate): bool => $rate->id === $id);
    }
}
