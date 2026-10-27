<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioPayment;
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioRevenue;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class FolioTest extends TestCase
{
    public function test_payment_and_revenue_postings_send_the_documented_body(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/folio/add-payment' => Http::response(['success' => true, 'message' => 'SP_RES_MAKE_PAYMENT_SUCCESS', 'transaction-id' => 470133231]),
            self::BASE.'hotel/26780/folio/add-revenue' => Http::response(['success' => true, 'message' => 'SP_RES_MAKE_PAYMENT_SUCCESS', 'transaction-id' => 470133232]),
        ]);

        $folio = $this->app->make(BookingApi::class)->hotel()->folio();

        $payment = $folio->addPayment(FolioPayment::make(95989318, 789, 250.004, 'eur', 13875.456));
        $revenue = $folio->addRevenue(FolioRevenue::make(95989318, 123, 456, 1000, 'TRY', 1000));

        $this->assertSame(470133231, $payment->transactionId);
        $this->assertSame(470133232, $revenue->transactionId);

        // JSON has no float/int distinction: compare loosely.
        $this->assertEquals([
            'reservation-id' => 95989318,
            'local-amount' => 13875.46,
            'amount' => 250.0,
            'currency-code' => 'EUR',
            'department-id' => 789,
        ], json_decode($this->requestsTo('/folio/add-payment')[0]->body(), true));

        $this->assertEquals([
            'reservation-id' => 95989318,
            'local-amount' => 1000,
            'amount' => 1000,
            'currency-code' => 'TRY',
            'department-id' => 123,
            'revenue-id' => 456,
        ], json_decode($this->requestsTo('/folio/add-revenue')[0]->body(), true));
    }

    public function test_a_posting_answered_with_success_false_throws(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/folio/add-payment' => Http::response(['success' => false, 'message' => 'RESERVATION_NOT_FOUND']),
        ]);

        $this->expectException(RequestFailedException::class);

        $this->app->make(BookingApi::class)->hotel()->folio()->addPayment(FolioPayment::make(95989318, 789, 100, 'TRY', 100));
    }

    public function test_a_posting_is_sent_once_even_when_retries_are_enabled(): void
    {
        config()->set('elektraweb.booking_api.http.retry.times', 2);
        config()->set('elektraweb.booking_api.http.retry.sleep_ms', 0);

        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/folio/add-payment' => Http::response('Bad Gateway', 502),
        ]);

        try {
            $this->app->make(BookingApi::class)->hotel()->folio()->addPayment(FolioPayment::make(95989318, 789, 100, 'TRY', 100));
            $this->fail('A 502 must surface as RequestFailedException.');
        } catch (RequestFailedException) {
            // The provider may have booked the payment before failing: a retry could post it twice.
        }

        $this->assertCount(1, $this->requestsTo('/folio/add-payment'));
    }

    public function test_entries_map_the_documented_folio_rows(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/folio/details*' => Http::response($this->fixture('folio-details.json')),
        ]);

        $entries = $this->app->make(BookingApi::class)->hotel()->folio()->entries(95989318, 95989319, 95989318);

        $request = $this->requestsTo('/folio/details')[0];
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $this->assertSame(['reservation-ids' => '95989318,95989319'], $query);

        $this->assertCount(4, $entries);

        $payment = $entries[1];
        $this->assertSame(470132457, $payment->id);
        $this->assertSame(95989318, $payment->reservationId);
        $this->assertSame(69214, $payment->departmentId);
        $this->assertSame('CityLedger', $payment->departmentName);
        $this->assertNull($payment->revenueId);
        $this->assertSame(-2.0, $payment->amount);
        $this->assertSame(-2.0, $payment->localAmount);
        $this->assertSame('GBP', $payment->currency);

        $charge = $entries[3];
        $this->assertSame(47031, $charge->revenueId);
        $this->assertSame('Accommodation', $charge->revenueName);
        $this->assertSame(3.0, $charge->amount);
        $this->assertSame('Seyf Sahin', $charge->guestName);
    }

    public function test_exchange_rate_converts_a_posting_amount_to_the_local_currency(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/exchange-rate' => Http::response([
                'rates-date' => '2026-10-26T00:00:00.000Z',
                'EUR' => 0.018018115413237,
                'TRY' => 1,
            ]),
        ]);

        $definitions = $this->app->make(BookingApi::class)->hotel()->definitions();

        $this->assertSame(['EUR', 'TRY'], $definitions->exchangeRates()->pluck('currency')->all());

        $eur = $definitions->exchangeRate('eur');
        $this->assertNotNull($eur);
        $this->assertSame('2026-10-26T00:00:00.000Z', $eur->date);
        $this->assertEqualsWithDelta(13874.92, $eur->toLocal(250.0), 0.01);
        $this->assertSame(100.0, $definitions->exchangeRate('TRY')?->toLocal(100.0));
        $this->assertNull($definitions->exchangeRate('USD'));
    }
}
