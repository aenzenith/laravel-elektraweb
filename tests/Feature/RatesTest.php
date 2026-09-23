<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\InvalidRequestException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\BookingApi\Requests\PriceSearch;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class RatesTest extends TestCase
{
    public function test_search_sends_the_documented_query_and_maps_offers(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/price/*' => Http::response($this->fixture('price.json')),
        ]);

        $search = PriceSearch::make('2022-12-20', '2022-12-25', 2)
            ->withChildren([4, 7])
            ->withCurrency('try')
            ->withNationality('tr')
            ->withPromoCode('SUMMER');

        $offers = $this->app->make(BookingApi::class)->hotel()->rates()->search($search);

        $request = $this->requestsTo('/price/')[0];
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        $this->assertSame([
            'fromdate' => '2022-12-20',
            'todate' => '2022-12-25',
            'adult' => '2',
            'childage' => '4,7',
            'currency' => 'TRY',
            'nationality' => 'TR',
            'onlybestoffer' => 'false',
            'language' => 'en',
            'promo-code' => 'SUMMER',
            'room-type-group-id' => '',
            'min-room-count' => '',
        ], $query);

        $this->assertCount(8, $offers);

        $first = $offers->first();
        $this->assertSame('26780-425897-57053-34882-685316-2022-12-20-2022-12-25', $first->id);
        $this->assertSame(5000.0, $first->price);
        $this->assertSame(4500.0, $first->totalPrice());
        $this->assertSame(10, $first->roomsToSell);
        $this->assertSame('TRY', $first->currency);
        $this->assertTrue($first->isBookable());
        $this->assertSame(7, $first->cancellationPenalty->periodInDays);

        $cheapest = $offers->cheapest();
        $this->assertNotNull($cheapest);
        $this->assertSame($offers->cheapestFirst()->min(fn ($offer) => $offer->totalPrice()), $cheapest->totalPrice());
    }

    public function test_success_false_body_means_no_offers_rather_than_an_error(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/price/*' => Http::response(['success' => false, 'message' => 'No availability']),
        ]);

        $offers = $this->app->make(BookingApi::class)->hotel()->rates()
            ->search(PriceSearch::make('2026-07-01', '2026-07-03'));

        $this->assertTrue($offers->isEmpty());
    }

    public function test_http_error_surfaces_as_request_failed_with_provider_message(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/price/*' => Http::response(['errorDetails' => ['fromdate' => ['must be in the future']]], 422),
        ]);

        try {
            $this->app->make(BookingApi::class)->hotel()->rates()->search(PriceSearch::make('2020-01-01', '2020-01-03'));
            $this->fail('Expected RequestFailedException.');
        } catch (RequestFailedException $exception) {
            $this->assertSame(422, $exception->status());
            $this->assertSame('fromdate: must be in the future', $exception->providerMessage());
            $this->assertTrue($exception->isClientError());
        }
    }

    public function test_invalid_stay_is_rejected_before_any_http_call(): void
    {
        Http::fake();

        $this->expectException(InvalidRequestException::class);

        PriceSearch::make('2026-07-05', '2026-07-01');
    }

    public function test_availability_maps_daily_rows(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/availability*' => Http::response($this->fixture('availability.json')),
        ]);

        $days = $this->app->make(BookingApi::class)->hotel()->rates()->availability('2022-12-20', '2022-12-25');

        $this->assertSame('2022-12-20', $days->first()->date);
        $this->assertSame(4250.0, $days->first()->single);
        $this->assertSame(6000.0, $days->first()->double);
        $this->assertSame(2500.0, $days->first()->price('younger-chd'));
    }
}
