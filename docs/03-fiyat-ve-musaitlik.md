# Fiyat ve müsaitlik

## Fiyat arama

`GET /hotel/{id}/price/` ucu, `PriceSearch` nesnesiyle çağrılır. Tarihler `Y-m-d` metni ya da
`DateTimeInterface` olabilir.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\PriceSearch;

$search = PriceSearch::make('2026-07-01', '2026-07-05', adults: 2)
    ->withChildren([4, 9])          // çocuk yaşları
    ->withCurrency('EUR')
    ->withNationality('TR')         // ISO 3166-1 alpha-2
    ->withLanguage('tr')
    ->withPromoCode('YAZ2026')
    ->withMinRoomCount(1);

$offers = BookingApi::hotel()->rates()->search($search);
```

| Metot | Sorgu parametresi |
| --- | --- |
| `make($checkIn, $checkOut, $adults)` | `fromdate`, `todate`, `adult` |
| `withChildren(iterable)` | `childage` (virgülle ayrılır) |
| `withCurrency(?string)` | `currency` |
| `withNationality(?string)` | `nationality` |
| `withLanguage(?string)` | `language` |
| `withPromoCode(?string)` | `promo-code` |
| `withRoomTypeGroup(int\|string\|null)` | `room-type-group-id` |
| `withMinRoomCount(?int)` | `min-room-count` |
| `onlyBestOffer(bool)` | `onlybestoffer` |

Para birimi ve dil verilmezse `BookingApi::withCurrency()` / `withLanguage()` değeri, o da yoksa config
değeri gönderilir. Çıkış tarihi girişten önce ya da aynı günse, yetişkin sayısı 1'den küçükse
`InvalidRequestException` HTTP çağrısından önce atılır. `$search->nights()` gece sayısını verir.

Yanıt boş liste ya da `{"success": false}` ise boş `OfferCollection` döner; bu bir hata değildir.
`bestOffers($search)`, aynı aramayı `onlybestoffer=true` ile yapar.

## Teklifler

`OfferCollection`, Laravel `Collection` sınıfını genişletir.

```php
$offers->bookable();                 // stop-sell, girişe / çıkışa kapalı ve tükenenler çıkarılır
$offers->refundable();
$offers->forRoomType(425897);
$offers->forBoardType(57053);
$offers->cheapestFirst();
$offers->cheapest();
$offers->find('26780-425897-57053-...');
```

Bir `Offer` nesnesinin başlıca alanları:

| Alan | Kaynak |
| --- | --- |
| `id` | `id` |
| `roomTypeId`, `roomTypeName` | `room-type-id`, `room-type` |
| `boardTypeId`, `boardTypeName` | `board-type-id`, `board-type` |
| `rateTypeId`, `rateCodeId`, `priceAgencyId`, `marketId` | ilgili `*-id` alanları |
| `price`, `discountedPrice` | `price`, `discounted-price` |
| `currency` | `currency` |
| `roomsToSell` | `room-to-sell` ya da `room-tosell` |
| `cancellationPenalty` | `cancellation-penalty` (`CancellationPenalty`) |
| `raw` | ham satır |

`totalPrice()` indirimli fiyatı, yoksa liste fiyatını verir. `isBookable()` satış kısıtlarını ve kalan
oda sayısını birlikte kontrol eder.

### Teklifi sonradan yeniden bulmak

Teklif kimliği tarihler ve fiyat kombinasyonundan üretilir; ödeme adımında yeniden arama yapıldığında
aynı kimlik dönmeyebilir. `equivalentTo()` oda, pansiyon, fiyat tipi, fiyat kodu, acente, para birimi,
oda sayısı ve pazar alanları eşleşen teklifler arasından fiyatı en yakın olanı döndürür.

```php
$current = $freshOffers->find($previous->id) ?? $freshOffers->equivalentTo($previous);

if ($current === null || ! $current->isBookable()) {
    // oda artık satılmıyor
}

if (abs($current->totalPrice() - $previous->totalPrice()) > 0.01) {
    // fiyat değişti
}
```

## Günlük müsaitlik

`GET /hotel/{id}/availability`, fiyat tipi başına günlük doluluk fiyatlarını döndürür.

```php
$days = BookingApi::hotel()->rates()->availability('2026-07-01', '2026-07-05');

foreach ($days as $day) {
    $day->date;          // 2026-07-01
    $day->double;        // "dbl"
    $day->price('younger-chd');
}
```

`single`, `double`, `triple`, `quad`, `extraBed` alanları tiplidir. Diğer tüm fiyat anahtarlarına
`price($key)` ile erişilir.
