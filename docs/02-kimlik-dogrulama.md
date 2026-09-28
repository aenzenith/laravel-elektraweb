# Kimlik doğrulama

Booking API iki yöntem kabul eder: `POST /login` ile alınan JWT ya da her istekte gönderilen Google
reCAPTCHA token'ı (`x-captcha`). Paket bunu `ELEKTRAWEB_BOOKING_AUTH_DRIVER` ile seçer.

| Sürücü | Giriş isteği |
| --- | --- |
| `api_key` | Boş gövde, `Authorization: Bearer <api anahtarı>` |
| `hotel_user` | `{"hotel-id", "usercode", "password"}` |
| `login_token` | `{"login-token"}` |
| `captcha` | Giriş yapılmaz |

## JWT yaşam döngüsü

1. İlk kimlik doğrulamalı istekte token depoda aranır.
2. Yoksa ya da süresi dolmuşsa `POST /login` çağrılır. Süre, JWT'nin `exp` alanından okunur; alan
   yoksa `token.ttl_minutes` kullanılır.
3. Token, kimlik bilgisinin özetinden türetilen anahtarla depoya yazılır. Gizli değer anahtarda yer almaz.
4. Herhangi bir uç `401` dönerse token silinir, bir kez yeniden giriş yapılır ve istek tekrarlanır.
   İkinci `401` hata olarak döner.

```php
$token = BookingApi::token();   // gerekirse giriş yapar
$token->value;                  // ham JWT
$token->expiresAt;              // ?DateTimeImmutable

BookingApi::login();            // depoyu atlayıp giriş yapar
BookingApi::forgetToken();      // bir sonraki istek yeniden giriş yapar
```

## Farklı kimlik bilgileri

```php
use Aenzenith\ElektraWeb\BookingApi\Auth\HotelUserCredentials;

$frontDesk = BookingApi::withCredentials(
    new HotelUserCredentials('26780', 'resepsiyon', 'gizli-parola')
);

$frontDesk->hotel(26780)->reservations()->inHouse();
```

`ApiKeyCredentials`, `HotelUserCredentials` ve `LoginTokenCredentials` aynı `Credentials` arayüzünü
uygular. Her kimlik bilgisinin token'ı ayrı saklanır.

## reCAPTCHA

`captcha` sürücüsünde giriş yapılmaz; token her istekte `withCaptcha()` ile verilmelidir. Token
verilmeden kimlik doğrulama gerektiren bir uç çağrılırsa `NotConfiguredException` atılır.

```php
BookingApi::withCaptcha($request->input('g-recaptcha-response'))
    ->hotel()
    ->rates()
    ->search($search);
```

Captcha, JWT sürücüleriyle birlikte de kullanılabilir; bu durumda iki başlık birlikte gönderilir.

## Token deposu

Varsayılan depo Laravel önbelleğidir (`CacheTokenStore`). Tokenların süreç dışına çıkmaması gerekiyorsa
bellek içi depo kullanılabilir:

```php
use Aenzenith\ElektraWeb\BookingApi\Auth\ArrayTokenStore;

$api = BookingApi::withTokenStore(new ArrayTokenStore());
```

Kendi deponuzu `TokenStore` arayüzünü (`get`, `put`, `forget`) uygulayarak yazabilirsiniz.
Başarılı her girişte `TokenIssued` olayı yayınlanır.
