<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Data;

use Aenzenith\ElektraWeb\Support\ArrayReader;

/**
 * GET /hotel/{id}/params
 *
 * The payload has dozens of sections; the most used ones get typed accessors,
 * everything else is reachable through {@see get()} with dot notation.
 */
final class HotelParams
{
    private readonly ArrayReader $reader;

    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public readonly array $raw,
    ) {
        $this->reader = ArrayReader::of($raw);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return $this->reader->get($path, $default);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function section(string $name): array
    {
        return $this->reader->array($name);
    }

    public function hotelId(): ?int
    {
        return $this->reader->nullableInt('general.id');
    }

    public function hotelName(): string
    {
        return $this->reader->string('general.name');
    }

    public function defaultLanguage(): string
    {
        return strtoupper($this->reader->string('general.default-language', 'EN'));
    }

    public function defaultCurrency(): string
    {
        return strtoupper($this->reader->string('general.default-currency', 'EUR'));
    }

    public function countryCode(): ?string
    {
        $code = $this->reader->nullableString('hotel-address.country-code');

        return $code !== null ? strtoupper($code) : null;
    }

    /**
     * @return list<string>
     */
    public function currencies(): array
    {
        return $this->upperList('header.hotel-currencies');
    }

    /**
     * @return list<string>
     */
    public function languages(): array
    {
        return $this->upperList('header.hotel-languages');
    }

    public function supportsCurrency(string $currency): bool
    {
        $currencies = $this->currencies();

        return $currencies === [] || in_array(strtoupper($currency), $currencies, true);
    }

    public function supportsLanguage(string $language): bool
    {
        $languages = $this->languages();

        return $languages === [] || in_array(strtoupper($language), $languages, true);
    }

    public function maxAdults(): int
    {
        return $this->reader->int('search-and-general-rules.max-adult', 6);
    }

    public function maxChildren(): int
    {
        return $this->reader->int('search-and-general-rules.max-child', 4);
    }

    public function minChildAge(): int
    {
        return $this->reader->int('search-and-general-rules.min-child-age', 0);
    }

    public function maxChildAge(): int
    {
        return $this->reader->int('search-and-general-rules.max-child-age', 12);
    }

    public function minLengthOfStay(): int
    {
        return max(1, $this->reader->int('search-and-general-rules.min-los', 1));
    }

    public function paysByCreditCard(): bool
    {
        return $this->reader->bool('payment.pay-by-credit-card');
    }

    public function paysByWireTransfer(): bool
    {
        return $this->reader->bool('payment.pay-by-wire-transfer');
    }

    public function paysByMailOrder(): bool
    {
        return $this->reader->bool('payment.pay-by-mail-order');
    }

    public function paysAtHotel(): bool
    {
        return $this->reader->bool('payment.pay-at-hotel');
    }

    public function bankInformation(): ?string
    {
        return $this->reader->nullableString('payment.bank-information');
    }

    public function extraPaymentInformation(): ?string
    {
        return $this->reader->nullableString('payment.extra-payment-information');
    }

    public function stars(): int
    {
        return $this->reader->int('hotel-info.stars');
    }

    public function defaultImage(): ?string
    {
        return $this->reader->nullableString('hotel-info.default-image');
    }

    public function phone(): ?string
    {
        return $this->reader->nullableString('seo-social-media.phone');
    }

    public function email(): ?string
    {
        return $this->reader->nullableString('seo-social-media.email');
    }

    /**
     * @return list<string>
     */
    private function upperList(string $path): array
    {
        $values = [];

        foreach ($this->reader->array($path) as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $values[] = strtoupper(trim((string) $value));
            }
        }

        return array_values(array_unique($values));
    }
}
