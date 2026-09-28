# Kurulum ve yapılandırma

## Gereksinimler

| Bileşen | Sürüm |
| --- | --- |
| PHP | 8.1 ve üzeri |
| Laravel | 9, 10, 11, 12, 13 |
| guzzlehttp/guzzle | ^7.4 |

## Kurulum

```bash
composer require aenzenith/laravel-elektraweb
php artisan vendor:publish --tag=elektraweb-config
```

Servis sağlayıcı ve `ElektraWeb`, `BookingApi` facade'ları paket keşfiyle otomatik kaydedilir.
Yayınlanan dosya `config/elektraweb.php` konumuna kopyalanır.

## Ortam değişkenleri

| Değişken | Varsayılan | Açıklama |
| --- | --- | --- |
| `ELEKTRAWEB_BOOKING_API_URL` | `https://bookingapi.elektraweb.com/` | API kök adresi |
| `ELEKTRAWEB_HOTEL_ID` | — | `BookingApi::hotel()` parametresiz çağrıldığında kullanılan otel |
| `ELEKTRAWEB_BOOKING_LANGUAGE` | `EN` | Dil parametresi alan uçlar için varsayılan |
| `ELEKTRAWEB_BOOKING_CURRENCY` | — | Para birimi parametresi alan uçlar için varsayılan |
| `ELEKTRAWEB_BOOKING_AUTH_DRIVER` | `api_key` | `api_key`, `hotel_user`, `login_token`, `captcha` |
| `ELEKTRAWEB_BOOKING_API_KEY` | — | `api_key` sürücüsü için |
| `ELEKTRAWEB_BOOKING_USERCODE` / `ELEKTRAWEB_BOOKING_PASSWORD` | — | `hotel_user` sürücüsü için |
| `ELEKTRAWEB_BOOKING_LOGIN_TOKEN` | — | `login_token` sürücüsü için |
| `ELEKTRAWEB_BOOKING_TOKEN_STORE` | varsayılan önbellek | JWT'nin saklandığı önbellek deposu |
| `ELEKTRAWEB_BOOKING_TOKEN_TTL` | `55` | JWT içinde `exp` yoksa kullanılan ömür (dakika) |
| `ELEKTRAWEB_BOOKING_TIMEOUT` | `30` | İstek zaman aşımı (saniye) |
| `ELEKTRAWEB_BOOKING_CONNECT_TIMEOUT` | `10` | Bağlantı zaman aşımı (saniye) |
| `ELEKTRAWEB_BOOKING_RETRY_TIMES` | `2` | Bağlantı hatası ve 5xx için ek deneme sayısı |
| `ELEKTRAWEB_BOOKING_RETRY_SLEEP` | `250` | Denemeler arası bekleme (milisaniye) |
| `ELEKTRAWEB_BOOKING_CACHE` | `true` | Referans veri önbelleği açık / kapalı |
| `ELEKTRAWEB_BOOKING_CACHE_STORE` | varsayılan önbellek | Referans verilerin saklandığı depo |

Önbellek süreleri (`cache.ttl_minutes`) ve bayat kopya ömrü (`cache.stale_minutes`) yalnız config
dosyasından ayarlanır; ayrıntılar [Önbellek](09-onbellek.md) sayfasında.

`http.options` anahtarına verilen dizi, her isteğe Guzzle seçeneği olarak eklenir (ör. `proxy`, `verify`).

## Modüle erişim

Üç yol aynı nesneyi döndürür:

```php
use Aenzenith\ElektraWeb\Facades\BookingApi;
use Aenzenith\ElektraWeb\Facades\ElektraWeb;

BookingApi::hotel();                      // facade
ElektraWeb::bookingApi()->hotel();        // modül yöneticisi
app(\Aenzenith\ElektraWeb\BookingApi\BookingApi::class)->hotel(); // container
```

Sınıf enjeksiyonu önerilen yoldur:

```php
use Aenzenith\ElektraWeb\BookingApi\BookingApi;

final class ReservationService
{
    public function __construct(private readonly BookingApi $bookingApi) {}
}
```

## Çağrı başına ayar

`BookingApi` değişmezdir. `with*` metotları ayarlanmış yeni bir örnek döndürür; singleton olarak
kaydedilen örnek değişmez. Bu sayede Octane ve kuyruk işçilerinde ayarlar istekler arasında taşınmaz.

| Metot | Etki |
| --- | --- |
| `withLanguage(string)` | Dil parametresi alan uçlar |
| `withCurrency(?string)` | Para birimi parametresi alan uçlar |
| `withCredentials(?Credentials)` | Config dışındaki kimlik bilgileriyle giriş |
| `withCaptcha(?string)` | Her isteğe `x-captcha` başlığı eklenir |
| `withHeaders(array)` | Her isteğe ek başlık |
| `withoutCache()` | Referans veri önbelleği bu örnek için devre dışı |
| `withTokenStore(TokenStore)` | JWT deposunu değiştirir |

```php
$english = BookingApi::withLanguage('EN');
$english->hotel()->definitions()->all();   // EN
BookingApi::hotel()->definitions()->all();   // config'deki dil
```

`artisan about` komutu, ElektraWeb başlığı altında API adresini, varsayılan oteli ve kimlik doğrulama
sürücüsünü listeler.
