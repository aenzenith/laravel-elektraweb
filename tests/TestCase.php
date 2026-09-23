<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests;

use Aenzenith\ElektraWeb\ElektraWebServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected const BASE = 'https://bookingapi.elektraweb.com/';

    protected const JWT = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ0ZXN0In0.c2lnbmF0dXJl';

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ElektraWebServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('elektraweb.booking_api.base_url', self::BASE);
        $app['config']->set('elektraweb.booking_api.hotel_id', 26780);
        $app['config']->set('elektraweb.booking_api.auth.driver', 'api_key');
        $app['config']->set('elektraweb.booking_api.auth.api_key', 'secret-api-key');
        $app['config']->set('elektraweb.booking_api.http.retry.times', 0);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function fixture(string $name): array
    {
        /** @var array<array-key, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents(__DIR__.'/Fixtures/'.$name), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loginOk(string $token = self::JWT): array
    {
        return [self::BASE.'login' => Http::response(['token' => $token])];
    }

    /**
     * All recorded requests to a path (suffix match), oldest first.
     *
     * @return list<Request>
     */
    protected function requestsTo(string $pathContains): array
    {
        $matches = [];

        Http::assertSentCount(count(Http::recorded()));

        foreach (Http::recorded() as [$request]) {
            if (str_contains($request->url(), $pathContains)) {
                $matches[] = $request;
            }
        }

        return $matches;
    }
}
