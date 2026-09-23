# Laravel ElektraWeb

[![Tests](https://github.com/aenzenith/laravel-elektraweb/actions/workflows/tests.yml/badge.svg)](https://github.com/aenzenith/laravel-elektraweb/actions/workflows/tests.yml)

A clean, typed Laravel SDK for [ElektraWeb](https://www.elektraweb.com/) APIs.
Ships the **Booking API** module today and is structured so further ElektraWeb
modules can be added under the same roof (`Aenzenith\ElektraWeb\<Module>`).

- PHP **8.1+** · Laravel **9, 10, 11, 12 and 13**
- Built on Laravel's HTTP client, so `Http::fake()` works out of the box
- Immutable request objects, typed response objects, provider-agnostic exceptions
- JWT login with cache-backed token reuse and automatic re-login on `401`
- Read-through cache with stale fallback for reference data (definitions, params, countries...)
- Every endpoint in the [public Booking API docs](https://hotel.docs.bookingapi.elektraweb.com/)

## Installation

```bash
composer require aenzenith/laravel-elektraweb
php artisan vendor:publish --tag=elektraweb-config
```

```dotenv
ELEKTRAWEB_HOTEL_ID=26780
ELEKTRAWEB_BOOKING_API_KEY=your-api-key
# optional
ELEKTRAWEB_BOOKING_API_URL=https://bookingapi.elektraweb.com/
ELEKTRAWEB_BOOKING_LANGUAGE=EN
ELEKTRAWEB_BOOKING_CURRENCY=EUR
```

See [`config/elektraweb.php`](config/elektraweb.php) for every option: auth
driver, timeouts, retries, token store and cache TTLs.

## Quick start

```php
use Aenzenith\ElektraWeb\Facades\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Requests\PriceSearch;
use Aenzenith\ElektraWeb\BookingApi\Requests\CreateReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\Guest;
use Aenzenith\ElektraWeb\BookingApi\Requests\Contact;
use Aenzenith\ElektraWeb\BookingApi\Enums\PaymentType;

$hotel = BookingApi::hotel(); // default hotel from config, or BookingApi::hotel(26780)

// 1. Search rates
$offers = $hotel->rates()->search(
    PriceSearch::make('2026-07-01', '2026-07-05', adults: 2)
        ->withChildren([4])
        ->withCurrency('EUR')
        ->withNationality('DE')
);

$offer = $offers->bookable()->cheapest();

// 2. Book it
$created = $hotel->reservations()->create(
    CreateReservation::fromOffer($offer, '2026-07-01', '2026-07-05', nationality: 'DE')
        ->withGuests([
            Guest::mr('Ada', 'Lovelace'),
            Guest::ms('Grace', 'Hopper'),
            Guest::child('Bo', 'Lovelace', birthday: '2022-03-02'),
        ])
        ->withContact(Contact::make('Ada', 'Lovelace', 'ada@example.com', '+49 30 1234567'))
        ->withPaymentType(PaymentType::NotPaid)
        ->withNotes('Late arrival')
);

$created->reservationId; // 101010101542
```

You can also inject the module instead of using the facade:

```php
use Aenzenith\ElektraWeb\BookingApi\BookingApi;

public function __construct(private BookingApi $bookingApi) {}
```

or go through the manager: `ElektraWeb::bookingApi()`.

## The API surface

Everything hangs off two entry points: `BookingApi::hotel($id)` for
hotel-scoped endpoints and `BookingApi::constants()` for system lists.

| Docs section | Call | Returns |
| --- | --- | --- |
| Login | `BookingApi::login()` / `token()` / `forgetToken()` | `AccessToken` |
| Hotel Definitions | `$hotel->definitions()->all()` | `HotelDefinitions` (room, board and rate types) |
| Params | `$hotel->definitions()->params()` | `HotelParams` |
| Exchange Rates | `$hotel->definitions()->exchangeRates()` | `Collection<ExchangeRate>` |
| Extra Services | `$hotel->definitions()->extraServices(ExtraServicesQuery)` | `Collection<ExtraService>` |
| Price | `$hotel->rates()->search(PriceSearch)` / `bestOffers()` | `OfferCollection` |
| Availability | `$hotel->rates()->availability($from, $to)` | `Collection<DailyAvailability>` |
| Create Reservation | `$hotel->reservations()->create(CreateReservation)` | `ReservationCreated` |
| Update Reservation | `$hotel->reservations()->update(UpdateReservation)` | `OperationResult` |
| Create Extra Services Reservation | `$hotel->reservations()->addServices(ServiceReservation)` | `OperationResult` |
| Cancel Reservation (B2E) | `$hotel->reservations()->cancel($reservationId)` | `OperationResult` |
| Reservation List (B2E) | `$hotel->reservations()->list(ReservationListFilter)` | `Collection<ReservationRecord>` |
| Departure List (B2E) | `$hotel->reservations()->departures($daysUpFront)` | `Collection<ReservationRecord>` |
| In-House List (B2E) | `$hotel->reservations()->inHouse()` | `Collection<ReservationRecord>` |
| Hotel Debt Totals (B2E) | `$hotel->reservations()->debtTotals($checkOutDate)` | `DebtTotals` |
| Find Guest User (B2E) | `$hotel->reservations()->findGuestUser($email)` | `?GuestUser` |
| Cancel by SMS, step 1 | `$hotel->guestCancellation()->find(GuestReservationLookup)` | `GuestReservationLookupResult` |
| Cancel by SMS, step 2 | `$hotel->guestCancellation()->confirm($smsCheckUid, $smsCode)` | `OperationResult` |
| Countries / Cities / Std. Board Types | `BookingApi::constants()->countries()` / `cities()` / `standardBoardTypes()` | `Collection<...>` |
| Health check | `BookingApi::healthCheck()` | `ApiResponse` |

Every response object keeps the untouched provider body in `->raw` (or
`->get('dot.path')`) so nothing the API returns is ever out of reach, even for
endpoints whose payloads are not documented.

### Working with offers

```php
$offers->bookable();              // stop-sell / sold-out filtered out
$offers->refundable();
$offers->forRoomType(425897);
$offers->cheapestFirst();
$offers->find($offerId);          // by the provider's offer id
$offers->equivalentTo($oldOffer); // same rate identity when the id changed (e.g. checkout after a re-search)

$offer->totalPrice();             // discounted price when present, else list price
$offer->isBookable();
$offer->cancellationPenalty->isRefundable;
```

### Per-call configuration

`BookingApi` is immutable; each `with*` returns a configured copy so tweaks
never leak to other callers (safe under Octane / queues).

```php
BookingApi::withLanguage('TR')->hotel()->definitions()->all();
BookingApi::withCurrency('USD')->hotel()->rates()->search($search);
BookingApi::withoutCache()->hotel()->definitions()->params();
BookingApi::withCaptcha($recaptchaToken)->hotel()->guestCancellation()->find($lookup);
BookingApi::withCredentials(new HotelUserCredentials('26780', 'user', 'pass'))->login();
BookingApi::withHeaders(['X-Request-Id' => $id])->hotel()->rates()->search($search);
```

### Authentication

| `ELEKTRAWEB_BOOKING_AUTH_DRIVER` | How it logs in |
| --- | --- |
| `api_key` (default) | `Authorization: Bearer <api key>` on `POST /login` |
| `hotel_user` | `hotel-id` + `usercode` + `password` in the login body |
| `login_token` | `login-token` in the login body |
| `captcha` | No login; every request carries the `x-captcha` header you pass via `withCaptcha()` |

Issued JWTs are stored in the configured cache store until they expire (the
`exp` claim is honoured, falling back to `token.ttl_minutes`). A `401` from any
endpoint triggers one transparent re-login and replay.

### Caching

Reference data is cached per bucket (`cache.ttl_minutes.*`). A second, longer
lived copy (`cache.stale_minutes`) is served when the provider is unreachable,
and a `StaleCacheServed` event is fired so you can alert on it. Prices,
availability and every write are never cached.

```php
$hotel->definitions()->forget();       // drop this hotel's cached definitions
BookingApi::constants()->forget();      // drop countries / cities / board types
```

### Errors

All exceptions extend `Aenzenith\ElektraWeb\Exceptions\ElektraWebException`.

| Exception | When |
| --- | --- |
| `NotConfiguredException` | Missing hotel id, secret or auth |
| `InvalidRequestException` | A request object is not sendable (no adults, child without birthday, check-out before check-in...) — thrown **before** any HTTP call |
| `ConnectionFailedException` | Provider unreachable (DNS, TLS, timeout) after retries |
| `AuthenticationException` | Login rejected or returned no token |
| `RequestFailedException` | Non-2xx, or a body with `"success": false`. Exposes `status()`, `providerMessage()`, `response` |
| `InvalidResponseException` | 2xx but the body does not have the documented shape |

A `RequestFailed` event is dispatched right before any of these is thrown, which
is a convenient single place to log provider problems.

### Events

`TokenIssued`, `RequestFailed`, `StaleCacheServed` — plus Laravel's own
`RequestSending` / `ResponseReceived` HTTP client events.

## Testing your integration

Because the SDK uses Laravel's HTTP client you fake it like any other call:

```php
Http::fake([
    'bookingapi.elektraweb.com/login' => Http::response(['token' => 'x.y.z']),
    'bookingapi.elektraweb.com/hotel/*/price/*' => Http::response([...]),
]);
```

## Extending with new modules

```php
ElektraWeb::extend('pms', PmsApi::class);   // implements Aenzenith\ElektraWeb\Contracts\Module
ElektraWeb::module('pms');
```

## Development

```bash
composer test      # phpunit
composer analyse   # phpstan level 8
composer format    # pint
```

The CI matrix runs PHP 8.1–8.5 against Laravel 9–13 with both
`prefer-lowest` and `prefer-stable` dependency sets.

## License

MIT — see [LICENSE](LICENSE).
