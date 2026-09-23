<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Events\StaleCacheServed;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\RequestFailedException;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

final class ReferenceCacheTest extends TestCase
{
    public function test_definitions_are_cached_and_served_stale_when_the_provider_fails(): void
    {
        Event::fake([StaleCacheServed::class]);
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/hotel-definitions*' => Http::sequence()
                ->push($this->fixture('hotel-definitions.json'))
                ->push(['message' => 'boom'], 503),
        ]);

        $api = $this->app->make(BookingApi::class);

        $first = $api->hotel()->definitions()->all();
        $this->assertSame('Suit', $first->roomTypes->first()->name);

        // Fresh copy (6h) expires, stale copy (3 days) remains.
        $this->travelTo(now()->addHours(7));

        $second = $api->hotel()->definitions()->all();

        $this->assertSame($first->raw, $second->raw);
        $this->assertCount(2, $this->requestsTo('/hotel-definitions'));
        Event::assertDispatched(StaleCacheServed::class);
    }

    public function test_without_cache_bypasses_reads_and_surfaces_errors(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/hotel-definitions*' => Http::sequence()
                ->push($this->fixture('hotel-definitions.json'))
                ->push(['message' => 'boom'], 503),
        ]);

        $api = $this->app->make(BookingApi::class)->withoutCache();
        $api->hotel()->definitions()->all();

        $this->expectException(RequestFailedException::class);

        $api->hotel()->definitions()->all();
    }

    public function test_forget_invalidates_fresh_and_stale_copies_of_a_hotel_scope(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/hotel-definitions*' => Http::response($this->fixture('hotel-definitions.json')),
            self::BASE.'hotel/26780/params*' => Http::response($this->fixture('params.json')),
            self::BASE.'countries' => Http::response($this->fixture('countries.json')),
        ]);

        $api = $this->app->make(BookingApi::class);

        $api->hotel()->definitions()->all();
        $api->hotel()->definitions()->params();
        $api->constants()->countries();

        $api->hotel()->definitions()->forget();

        $api->hotel()->definitions()->all();
        $api->hotel()->definitions()->params();
        $api->constants()->countries();

        $this->assertCount(2, $this->requestsTo('/hotel-definitions'), 'definitions refetched after forget()');
        $this->assertCount(2, $this->requestsTo('/params'), 'params share the hotel scope and are refetched too');
        $this->assertCount(1, $this->requestsTo('/countries'), 'constants scope is untouched');
    }

    public function test_params_expose_hotel_rules_and_are_cached_per_language(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'hotel/26780/params*' => Http::response($this->fixture('params.json')),
        ]);

        $api = $this->app->make(BookingApi::class);

        $params = $api->hotel()->definitions()->params();
        $api->hotel()->definitions()->params();
        $api->withLanguage('tr')->hotel()->definitions()->params();

        $this->assertSame('Granada Luxury Belek', $params->hotelName());
        $this->assertSame('EUR', $params->defaultCurrency());
        $this->assertSame('TR', $params->defaultLanguage());
        $this->assertGreaterThan(0, $params->maxAdults());
        $this->assertCount(2, $this->requestsTo('/params'));
    }
}
