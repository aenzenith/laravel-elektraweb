# Test

Paket Laravel HTTP client kullandığından istekler `Http::fake()` ile taklit edilir. Giriş isteğini de
taklit etmeyi unutmayın.

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'bookingapi.elektraweb.com/login' => Http::response(['token' => 'eyJhbGciOiJIUzI1NiJ9.e30.imza']),
    'bookingapi.elektraweb.com/hotel/26780/price/*' => Http::response([
        [
            'id' => '26780-425897-57053-34882-685316-2026-07-01-2026-07-05',
            'room-type-id' => 425897,
            'room-type' => 'Standart',
            'board-type-id' => 57053,
            'rate-type-id' => 34882,
            'rate-code-id' => 685316,
            'price-agency-id' => 459,
            'room-tosell' => 5,
            'price' => 5000,
            'discounted-price' => 4500,
            'currency' => 'TRY',
        ],
    ]),
    'bookingapi.elektraweb.com/hotel/26780/createReservation' => Http::response([
        'success' => true,
        'reservation-id' => 96999662,
    ]),
]);
```

Gönderilen gövdeyi doğrulamak için:

```php
Http::assertSent(function ($request) {
    return str_contains($request->url(), 'createReservation')
        && $request['payment-type'] === 2
        && $request['adult-count'] === 2;
});
```

Referans veri önbelleği testler arasında sonuç taşıyabilir. Test ortamında `array` önbellek sürücüsü
kullanın ya da çağrıyı `withoutCache()` ile yapın.

İstek nesnelerinin gövdesi ağ çağrısı olmadan da incelenebilir:

```php
$payload = $reservationRequest->toPayload(hotelId: 26780);
```
