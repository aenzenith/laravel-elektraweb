<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\Support\Dates;
use DateTimeInterface;

/**
 * Query for GET /hotel/{id}/price/
 *
 * Immutable: every `with*` returns a modified copy.
 */
final class PriceSearch
{
    /**
     * @var list<int>
     */
    private array $childAges = [];

    private ?string $currency = null;

    private ?string $nationality = null;

    private ?string $language = null;

    private ?string $promoCode = null;

    private ?string $roomTypeGroupId = null;

    private ?int $minRoomCount = null;

    private bool $onlyBestOffer = false;

    private function __construct(
        private readonly string $checkIn,
        private readonly string $checkOut,
        private readonly int $adults,
    ) {}

    public static function make(DateTimeInterface|string $checkIn, DateTimeInterface|string $checkOut, int $adults = 2): self
    {
        $from = Dates::toApi($checkIn);
        $to = Dates::toApi($checkOut);

        if ($to <= $from) {
            throw InvalidRequestException::because('Check-out must be after check-in.');
        }

        if ($adults < 1) {
            throw InvalidRequestException::because('At least one adult is required.');
        }

        return new self($from, $to, $adults);
    }

    /**
     * @param  iterable<int|string|null>  $ages
     */
    public function withChildren(iterable $ages): self
    {
        $normalized = [];

        foreach ($ages as $age) {
            if ($age === null || $age === '') {
                continue;
            }

            if ((int) $age < 0) {
                throw InvalidRequestException::because('Child ages cannot be negative.');
            }

            $normalized[] = (int) $age;
        }

        $copy = clone $this;
        $copy->childAges = $normalized;

        return $copy;
    }

    public function withCurrency(?string $currency): self
    {
        $copy = clone $this;
        $copy->currency = self::upperOrNull($currency);

        return $copy;
    }

    public function withNationality(?string $countryCode2): self
    {
        $copy = clone $this;
        $copy->nationality = self::upperOrNull($countryCode2);

        return $copy;
    }

    public function withLanguage(?string $language): self
    {
        $copy = clone $this;
        $copy->language = $language !== null && trim($language) !== '' ? strtolower(trim($language)) : null;

        return $copy;
    }

    public function withPromoCode(?string $promoCode): self
    {
        $copy = clone $this;
        $copy->promoCode = $promoCode !== null && trim($promoCode) !== '' ? trim($promoCode) : null;

        return $copy;
    }

    public function withRoomTypeGroup(int|string|null $roomTypeGroupId): self
    {
        $copy = clone $this;
        $copy->roomTypeGroupId = $roomTypeGroupId !== null && (string) $roomTypeGroupId !== '' ? (string) $roomTypeGroupId : null;

        return $copy;
    }

    public function withMinRoomCount(?int $minRoomCount): self
    {
        if ($minRoomCount !== null && $minRoomCount < 1) {
            throw InvalidRequestException::because('Minimum room count must be at least 1.');
        }

        $copy = clone $this;
        $copy->minRoomCount = $minRoomCount;

        return $copy;
    }

    public function onlyBestOffer(bool $onlyBestOffer = true): self
    {
        $copy = clone $this;
        $copy->onlyBestOffer = $onlyBestOffer;

        return $copy;
    }

    public function checkIn(): string
    {
        return $this->checkIn;
    }

    public function checkOut(): string
    {
        return $this->checkOut;
    }

    public function adults(): int
    {
        return $this->adults;
    }

    /**
     * @return list<int>
     */
    public function childAges(): array
    {
        return $this->childAges;
    }

    public function children(): int
    {
        return count($this->childAges);
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function nationality(): ?string
    {
        return $this->nationality;
    }

    public function language(): ?string
    {
        return $this->language;
    }

    public function promoCode(): ?string
    {
        return $this->promoCode;
    }

    public function nights(): int
    {
        return Dates::nightsBetween($this->checkIn, $this->checkOut);
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(string $defaultLanguage, ?string $defaultCurrency = null): array
    {
        return [
            'fromdate' => $this->checkIn,
            'todate' => $this->checkOut,
            'adult' => $this->adults,
            'childage' => implode(',', $this->childAges),
            'currency' => $this->currency ?? $defaultCurrency ?? '',
            'nationality' => $this->nationality ?? '',
            'onlybestoffer' => $this->onlyBestOffer,
            'language' => strtolower($this->language ?? $defaultLanguage),
            'promo-code' => $this->promoCode ?? '',
            'room-type-group-id' => $this->roomTypeGroupId ?? '',
            'min-room-count' => $this->minRoomCount ?? '',
        ];
    }

    private static function upperOrNull(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return strtoupper(trim($value));
    }
}
