<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

/**
 * Query for GET /hotel/{id}/service
 */
final class ExtraServicesQuery
{
    private ?string $language = null;

    private ?string $currency = null;

    private bool $sellWithoutReservation = true;

    private ?int $marketId = null;

    public static function make(): self
    {
        return new self;
    }

    public function withLanguage(?string $language): self
    {
        $copy = clone $this;
        $copy->language = $language !== null && trim($language) !== '' ? strtoupper(trim($language)) : null;

        return $copy;
    }

    public function withCurrency(?string $currency): self
    {
        $copy = clone $this;
        $copy->currency = $currency !== null && trim($currency) !== '' ? strtoupper(trim($currency)) : null;

        return $copy;
    }

    public function sellWithoutReservation(bool $sellWithoutReservation = true): self
    {
        $copy = clone $this;
        $copy->sellWithoutReservation = $sellWithoutReservation;

        return $copy;
    }

    public function withMarket(?int $marketId): self
    {
        $copy = clone $this;
        $copy->marketId = $marketId;

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(string $defaultLanguage, ?string $defaultCurrency = null): array
    {
        return [
            'language' => strtoupper($this->language ?? $defaultLanguage),
            'currency' => $this->currency ?? $defaultCurrency ?? '',
            'sell-without-res' => $this->sellWithoutReservation,
            'market-id' => $this->marketId ?? '',
        ];
    }
}
