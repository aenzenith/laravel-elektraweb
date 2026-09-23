<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Enums\TaxType;
use Aenzenith\ElektraWeb\Support\Payload;

/**
 * Invoice details ("tax-*" fields) for createReservation.
 */
final class TaxDetails
{
    public function __construct(
        public readonly TaxType $type,
        public readonly ?string $company = null,
        public readonly ?string $number = null,
        public readonly ?string $place = null,
        public readonly ?string $address = null,
    ) {}

    public static function personal(?string $number = null, ?string $address = null): self
    {
        return new self(TaxType::Personal, null, $number, null, $address);
    }

    public static function company(string $company, string $number, ?string $place = null, ?string $address = null): self
    {
        return new self(TaxType::Company, $company, $number, $place, $address);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'tax-type' => $this->type->value,
            'tax-company' => $this->company,
            'tax-no' => $this->number,
            'tax-place' => $this->place,
            'tax-address' => $this->address,
        ]);
    }
}
