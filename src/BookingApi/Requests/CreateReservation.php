<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\BookingApi\Requests;

use Aenzenith\ElektraWeb\BookingApi\Data\Offer;
use Aenzenith\ElektraWeb\BookingApi\Requests\Concerns\HasReservationDetails;
use Aenzenith\ElektraWeb\Support\Payload;
use DateTimeInterface;

/**
 * Body for POST /hotel/{id}/createReservation
 *
 * Typical usage:
 *
 *     CreateReservation::fromOffer($offer, '2026-07-01', '2026-07-05', 'TR')
 *         ->withGuests([Guest::mr('Ahmet', 'Yılmaz'), Guest::child('Elif', 'Yılmaz', '2019-03-02')])
 *         ->withContact(Contact::make('Ahmet', 'Yılmaz', 'ahmet.yilmaz@ornek.com', '+905321112233'))
 *         ->withPaymentType(PaymentType::CreditCard);
 */
final class CreateReservation
{
    use HasReservationDetails;

    private ?Contact $contact = null;

    private ?string $notes = null;

    private ?TaxDetails $tax = null;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    /**
     * Start from a price search result. Stay and nationality are the values the search was made with.
     */
    public static function fromOffer(Offer $offer, DateTimeInterface|string $checkIn, DateTimeInterface|string $checkOut, string $nationality): self
    {
        return (new self)
            ->forOffer($offer)
            ->withStay($checkIn, $checkOut)
            ->withNationality($nationality);
    }

    public function withContact(?Contact $contact): self
    {
        $copy = clone $this;
        $copy->contact = $contact;

        return $copy;
    }

    public function withNotes(?string $notes): self
    {
        $copy = clone $this;
        $copy->notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        return $copy;
    }

    public function withTax(?TaxDetails $tax): self
    {
        $copy = clone $this;
        $copy->tax = $tax;

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(int $hotelId): array
    {
        return array_replace(
            $this->sharedPayload($hotelId),
            $this->contact?->toPayload() ?? [],
            $this->tax?->toPayload() ?? [],
            Payload::withoutNulls([
                'res-notes' => $this->notes,
            ]),
        );
    }
}
