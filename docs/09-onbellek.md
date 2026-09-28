# Önbellek

Seyrek değişen referans veriler Laravel önbelleğinde tutulur. Fiyat, müsaitlik ve tüm yazma
işlemleri önbelleğe alınmaz.

| Kova | Uçlar | Varsayılan süre |
| --- | --- | --- |
| `definitions` | `hotel-definitions` | 360 dk |
| `params` | `params` | 15 dk |
| `exchange_rates` | `exchange-rate` | 60 dk |
| `extra_services` | `service` | 60 dk |
| `constants` | `countries`, `cities`, `std-board-type` | 7 gün |

Süreler `config/elektraweb.php` içindeki `booking_api.cache.ttl_minutes` altında değiştirilir.
Bir kovanın süresi `0` yapılırsa o kova önbelleğe alınmaz.

## Bayat kopya

Her başarılı yanıt ikinci bir kopya olarak `cache.stale_minutes` (varsayılan 3 gün) boyunca saklanır.
Taze kopyanın süresi dolduğunda sağlayıcıya ulaşılamazsa:

1. bayat kopya döndürülür,
2. sağlayıcıya art arda istek atılmaması için bu kopya en fazla 5 dakika taze kabul edilir,
3. `StaleCacheServed` olayı yayınlanır.

Bayat kopya da yoksa özgün istisna atılır.

```php
use Aenzenith\ElektraWeb\BookingApi\Events\StaleCacheServed;

Event::listen(function (StaleCacheServed $event) {
    Log::warning('ElektraWeb bayat veri', ['anahtar' => $event->key, 'hata' => $event->cause->getMessage()]);
});
```

## Önbelleği temizleme

Kayıtlar kapsamlara ayrılır: her otel için `hotel:{id}`, sistem listeleri için `constants`.
`forget()` kapsamın nesil sayacını artırır; eski anahtarlar okunmaz hâle gelir ve süreleri dolunca
silinir. Joker karakterli silme gerektirmez, her önbellek sürücüsünde çalışır.

```php
BookingApi::hotel()->definitions()->forget();   // otelin tüm referans verileri
BookingApi::constants()->forget();
```

## Devre dışı bırakma

```php
BookingApi::withoutCache()->hotel()->definitions()->params();   // bu çağrı için
```

Tamamen kapatmak için `ELEKTRAWEB_BOOKING_CACHE=false`.
