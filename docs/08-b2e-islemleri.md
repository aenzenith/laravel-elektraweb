# B2E işlemleri

Otel içi kullanım için tanımlanmış uçlar. Bu uçlar genellikle otel kullanıcısı yetkisi gerektirir;
`hotel_user` sürücüsü ya da `withCredentials()` ile kullanın.

Dokümanda bu uçlar için örnek yanıt yoktur. `ReservationRecord` sık görülen alanları
(`reservationId`, `voucherNo`, `guestNames`, `checkIn`, `checkOut`, `status`, `totalPrice`, `currency`)
birkaç olası adla okur; diğer alanlar `raw` ya da `get()` ile alınır.

## Rezervasyon listesi

```php
use Aenzenith\ElektraWeb\BookingApi\Enums\ReservationStatus;
use Aenzenith\ElektraWeb\BookingApi\Requests\ReservationListFilter;

$list = BookingApi::hotel()->reservations()->list(
    ReservationListFilter::checkInBetween('2026-07-01', '2026-07-31')
        ->withStatus(ReservationStatus::Reservation)
);
```

`ReservationStatus`: `Reservation`, `InHouse`, `CheckOut`, `Cancelled`. Tek bir rezervasyon için
`withReservationId()` kullanılır.

## Çıkış ve konaklayan listeleri

```php
$reservations = BookingApi::hotel()->reservations();

$reservations->departures();              // bugün çıkacaklar
$reservations->departures(daysUpFront: 2); // iki gün sonrasına kadar
$reservations->inHouse();
```

## Borç toplamları

```php
$debts = BookingApi::hotel()->reservations()->debtTotals('2026-07-05');

$debts->rows();          // liste ya da tek satır
$debts->get('0.total');
```

## Misafir kullanıcı arama

```php
$guest = BookingApi::hotel()->reservations()->findGuestUser('selin.arslan@ornek.com');

$guest?->name;   // eşleşme yoksa null
```

`404`, boş yanıt ya da `{"success": false}` durumunda `null` döner.
