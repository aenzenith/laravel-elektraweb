<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\GuestReservationLookup;
use Aenzenith\ElektraWeb\Facades\BookingApi;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class GuestCancellationTest extends TestCase
{
    public function test_lookup_returns_candidates_when_several_reservations_match(): void
    {
        config()->set('elektraweb.booking_api.auth.driver', 'captcha');
        Http::fake([
            self::BASE.'hotel/24204/findGuestReservation' => Http::response($this->fixture('find-guest-reservation-multi.json')),
        ]);

        $result = BookingApi::withCaptcha('captcha-token')->hotel(24204)->guestCancellation()->find(
            GuestReservationLookup::forCheckIn('2023-04-10')->withPhoneNumber('+905555555555')
        );

        $this->assertTrue($result->needsSelection());
        $this->assertFalse($result->smsSent());
        $this->assertSame([40845821, 40845900], $result->candidates->map(fn ($c) => $c->reservationNo)->all());

        Http::assertSent(fn (Request $request): bool => $request->header('x-captcha') === ['captcha-token']
            && ! $request->hasHeader('Authorization')
            && json_decode($request->body(), true) === [
                'check-in' => '2023-04-10', 'surname' => '', 'voucher-no' => '', 'phone-number' => '+905555555555', 'reservation-no' => null,
            ]);
    }

    public function test_lookup_returns_sms_uid_when_one_reservation_matches(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/findGuestReservation' => Http::response($this->fixture('find-guest-reservation-sms.json')),
        ]);

        $result = BookingApi::hotel()->guestCancellation()->find(
            GuestReservationLookup::forCheckIn('2023-04-10')->withReservationNo(40845821)
        );

        $this->assertTrue($result->smsSent());
        $this->assertSame('56a6eddb-f051-4517-8b8b-101d016fc981', $result->smsCheckUid);
    }

    public function test_confirm_with_wrong_code_throws(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/cancelGuestReservation' => Http::response(['success' => false, 'message' => 'Wrong code']),
        ]);

        $this->expectException(RequestFailedException::class);

        BookingApi::hotel()->guestCancellation()->confirm('uid', '000000');
    }
}
