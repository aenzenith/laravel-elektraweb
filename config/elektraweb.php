<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Booking API
    |--------------------------------------------------------------------------
    |
    | Settings for the ElektraWeb Booking API module. Every value can be
    | overridden per call through the fluent API, these are the defaults.
    |
    */

    'booking_api' => [

        // Root URL of the Booking API. Trailing slash is optional.
        'base_url' => env('ELEKTRAWEB_BOOKING_API_URL', 'https://bookingapi.elektraweb.com/'),

        // Default hotel. Any hotel-scoped call uses this when no explicit id is given.
        'hotel_id' => env('ELEKTRAWEB_HOTEL_ID'),

        // Default language (ISO 639-1) and currency (ISO 4217) sent with requests
        // that accept them. Both can be overridden on the request objects.
        'language' => env('ELEKTRAWEB_BOOKING_LANGUAGE', 'EN'),
        'currency' => env('ELEKTRAWEB_BOOKING_CURRENCY'),

        /*
        | Authentication.
        |
        | driver:
        |   api_key      Bearer API key exchanged for a JWT via POST /login (recommended).
        |   hotel_user   hotel-id + usercode + password exchanged for a JWT.
        |   login_token  one-time "login-token" exchanged for a JWT.
        |   captcha      no login; every request carries an "x-captcha" header
        |                supplied at runtime via ->withCaptcha().
        */
        'auth' => [
            'driver' => env('ELEKTRAWEB_BOOKING_AUTH_DRIVER', 'api_key'),
            'api_key' => env('ELEKTRAWEB_BOOKING_API_KEY'),
            'usercode' => env('ELEKTRAWEB_BOOKING_USERCODE'),
            'password' => env('ELEKTRAWEB_BOOKING_PASSWORD'),
            'login_token' => env('ELEKTRAWEB_BOOKING_LOGIN_TOKEN'),
        ],

        // Issued JWTs are cached so /login is not called on every request.
        'token' => [
            'store' => env('ELEKTRAWEB_BOOKING_TOKEN_STORE'), // null = default cache store
            'ttl_minutes' => (int) env('ELEKTRAWEB_BOOKING_TOKEN_TTL', 55),
            'prefix' => 'elektraweb:booking:token',
        ],

        // HTTP transport.
        'http' => [
            'timeout' => (int) env('ELEKTRAWEB_BOOKING_TIMEOUT', 30),
            'connect_timeout' => (int) env('ELEKTRAWEB_BOOKING_CONNECT_TIMEOUT', 10),
            // Transport-level retries for connection errors and 5xx responses.
            'retry' => [
                'times' => (int) env('ELEKTRAWEB_BOOKING_RETRY_TIMES', 2),
                'sleep_ms' => (int) env('ELEKTRAWEB_BOOKING_RETRY_SLEEP', 250),
            ],
            // Extra Guzzle options merged into every request.
            'options' => [],
        ],

        /*
        | Read-through cache for slowly changing reference data. When the
        | provider is unreachable a stale copy is served for `stale_minutes`
        | so the booking engine keeps working during short outages.
        */
        'cache' => [
            'enabled' => (bool) env('ELEKTRAWEB_BOOKING_CACHE', true),
            'store' => env('ELEKTRAWEB_BOOKING_CACHE_STORE'), // null = default cache store
            'prefix' => 'elektraweb:booking',
            'stale_minutes' => 60 * 24 * 3,
            'ttl_minutes' => [
                'definitions' => 60 * 6,
                'params' => 15,
                'exchange_rates' => 60,
                'extra_services' => 60,
                'constants' => 60 * 24 * 7,
            ],
        ],
    ],

];
