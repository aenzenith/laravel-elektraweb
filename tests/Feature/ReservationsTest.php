<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Data\OfferCollection;
use Aenzenith\ElektraWeb\BookingApi\Enums\GuestTitle;
use Aenzenith\ElektraWeb\BookingApi\Enums\PaymentType;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\Contact;
use Aenzenith\ElektraWeb\BookingApi\Requests\CreateReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\Guest;
use Aenzenith\ElektraWeb\BookingApi\Requests\ServiceLine;
use Aenzenith\ElektraWeb\BookingApi\Requests\ServiceReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\TaxDetails;
use Aenzenith\ElektraWeb\BookingApi\Requests\UpdateReservation;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class ReservationsTest extends TestCase
{
    public function test_create_reservation_sends_the_documented_payload_built_from_an_offer(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/createReservation' => Http::response(['success' => true, 'reservation-id' => 101010101542]),
        ]);

        $offer = OfferCollection::fromRows($this->fixture('price.json'))->first();

        $reservation = CreateReservation::fromOffer($offer, '2022-12-20', '2022-12-25', 'tr')
            ->withGuests([
                Guest::mr('Test', 'TestSurName')->withCountry('TR')->withBirthday('2000-01-01'),
                Guest::ms('Ada', 'Lovelace'),
                Guest::child('Bo', 'Lovelace', '2019-03-02'),
                Guest::baby('Cy', 'Lovelace', '2025-11-30'),
            ])
            ->withContact(Contact::make('Test', 'TestSurName', 'test@test.com', '+90 (555) 000 00 00'))
            ->withNotes('Test Note')
            ->withVoucherNo('TESTVOUCHERNO')
            ->withPaymentType(PaymentType::NotPaid)
            ->withTax(TaxDetails::company('ACME Ltd', '1234567890', 'Istanbul', 'Some street 1'));

        $created = $this->app->make(BookingApi::class)->hotel()->reservations()->create($reservation);

        $this->assertSame(101010101542, $created->reservationId);

        $payload = json_decode($this->requestsTo('/createReservation')[0]->body(), true);

        // JSON has no float/int distinction: compare loosely.
        $this->assertEquals([
            'hotel-id' => 26780,
            'rate-type-id' => 34882,
            'board-type-id' => 57053,
            'rate-code-id' => 685316,
            'room-type-id' => 425897,
            'currency-code' => 'TRY',
            'total-price' => 4500.0,
            'price-agency-id' => 459,
            'adult-count' => 2,
            'nationality' => 'TR',
            'check-in' => '2022-12-20',
            'check-out' => '2022-12-25',
            'elder-child-count' => 1,
            'younger-child-count' => 0,
            'baby-count' => 1,
            'guest-list' => [
                ['title-id' => 0, 'gender' => 0, 'country' => 'TR', 'name' => 'Test', 'surname' => 'TestSurName', 'birthday' => '2000-01-01'],
                ['title-id' => 1, 'gender' => 1, 'name' => 'Ada', 'surname' => 'Lovelace'],
                ['title-id' => 2, 'name' => 'Bo', 'surname' => 'Lovelace', 'birthday' => '2019-03-02'],
                ['title-id' => 3, 'name' => 'Cy', 'surname' => 'Lovelace', 'birthday' => '2025-11-30'],
            ],
            'room-count' => 1,
            'voucher-no' => 'TESTVOUCHERNO',
            'contact-first-name' => 'Test',
            'contact-last-name' => 'TestSurName',
            'contact-email' => 'test@test.com',
            'contact-phone' => '+905550000000',
            'tax-type' => 1,
            'tax-company' => 'ACME Ltd',
            'tax-no' => '1234567890',
            'tax-place' => 'Istanbul',
            'tax-address' => 'Some street 1',
            'res-notes' => 'Test Note',
            'payment-type' => 2,
        ], $payload);

        $this->assertArrayNotHasKey('market-id', $payload, 'Offers without a market must not send market-id.');
    }

    public function test_create_reservation_failure_body_throws_with_provider_message(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/createReservation' => Http::response(['success' => false, 'message' => 'Room is not available']),
        ]);

        $offer = OfferCollection::fromRows($this->fixture('price.json'))->first();

        try {
            $this->app->make(BookingApi::class)->hotel()->reservations()->create(
                CreateReservation::fromOffer($offer, '2022-12-20', '2022-12-25', 'TR')->withOccupancy(2)
            );
            $this->fail('Expected RequestFailedException.');
        } catch (RequestFailedException $exception) {
            $this->assertSame('Room is not available', $exception->providerMessage());
            $this->assertSame(200, $exception->status());
        }
    }

    public function test_child_without_birthday_is_rejected_before_sending(): void
    {
        Http::fake();
        $offer = OfferCollection::fromRows($this->fixture('price.json'))->first();

        $reservation = CreateReservation::fromOffer($offer, '2022-12-20', '2022-12-25', 'TR')
            ->withGuests([Guest::mr('A', 'B'), Guest::withTitle(GuestTitle::Child, 'C', 'D')]);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('needs a birthday');

        $this->app->make(BookingApi::class)->hotel()->reservations()->create($reservation);

        Http::assertNothingSent();
    }

    public function test_reservation_without_adults_is_rejected_before_sending(): void
    {
        Http::fake();
        $offer = OfferCollection::fromRows($this->fixture('price.json'))->first();

        $this->expectException(InvalidRequestException::class);

        $this->app->make(BookingApi::class)->hotel()->reservations()->create(
            CreateReservation::fromOffer($offer, '2022-12-20', '2022-12-25', 'TR')
                ->withGuests([Guest::child('C', 'D', '2020-01-01')])
        );
    }

    public function test_update_reservation_includes_reservation_id_and_update_only_fields(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/updateReservation' => Http::response(['success' => true, 'message' => 'updated']),
        ]);

        $offer = OfferCollection::fromRows($this->fixture('price.json'))->first();

        $result = $this->app->make(BookingApi::class)->hotel()->reservations()->update(
            UpdateReservation::fromOffer(123123123, $offer, '2023-10-01', '2023-10-10', 'TR')
                ->withOccupancy(2)
                ->withAgencyCommission(5.0)
                ->withPromoCode('X')
        );

        $this->assertTrue($result->success);
        $payload = json_decode($this->requestsTo('/updateReservation')[0]->body(), true);
        $this->assertSame(123123123, $payload['reservation-id']);
        $this->assertSame(26780, $payload['hotel-id']);
        $this->assertEquals(5.0, $payload['agency-commission']);
        $this->assertSame('X', $payload['promo-code']);
    }

    public function test_service_reservation_totals_lines_and_pads_extra_questions(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/createServiceReservation' => Http::response(['success' => true]),
        ]);

        $this->app->make(BookingApi::class)->hotel()->reservations()->addServices(
            ServiceReservation::for(45061164, 'try')
                ->addLine(ServiceLine::make(5879, '2023-06-25', 1)->withUnits(1))
                ->addLine(ServiceLine::make(5875, '2023-06-25', 140)->withPax(2, 0)->withAnswer('nereden ?', 'Antalya'))
        );

        $payload = json_decode($this->requestsTo('/createServiceReservation')[0]->body(), true);

        $this->assertSame(45061164, $payload['res-id']);
        $this->assertEquals(141.0, $payload['total-price']);
        $this->assertSame('TRY', $payload['currency']);
        $this->assertCount(2, $payload['services-list']);
        $this->assertSame('nereden ?', $payload['services-list'][1]['extra-question-1']);
        $this->assertSame('Antalya', $payload['services-list'][1]['extra-question-1-answer']);
        $this->assertNull($payload['services-list'][1]['extra-question-4-answer']);
        $this->assertNull($payload['services-list'][0]['adult-count']);
    }

    public function test_cancel_sends_reservation_id_and_returns_operation_result(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/cancel-reservation' => Http::response(['success' => true, 'message' => 'Reservation canceled successfully!']),
        ]);

        $result = $this->app->make(BookingApi::class)->hotel()->reservations()->cancel(1234133);

        $this->assertTrue($result->success);
        $this->assertSame(['reservation-id' => 1234133], json_decode($this->requestsTo('/cancel-reservation')[0]->body(), true));
    }
}
