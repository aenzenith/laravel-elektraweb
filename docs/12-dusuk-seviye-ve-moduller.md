# Düşük seviyeli erişim ve yeni modüller

## Modellenmemiş uçlar

Pakette karşılığı olmayan bir uç için `connector()` kullanılır. Kimlik doğrulama, captcha, ek başlıklar,
`401` sonrası yeniden giriş ve hata dönüşümü aynen uygulanır.

```php
$connector = BookingApi::connector();

$response = $connector->get('hotel/26780/yeni-uc', ['language' => 'TR']);
$response = $connector->post('hotel/26780/yeni-islem', ['reservation-id' => 96999662]);

// HTTP hatasında istisna atmadan yanıtı almak
$response = $connector->request('GET', 'health-check', authenticate: false, throw: false);
```

`ApiResponse`:

| Metot | Açıklama |
| --- | --- |
| `status()` | HTTP durum kodu |
| `successful()` | 2xx ve gövdede `"success": false` yok |
| `successFlag()` | Gövdedeki `success` alanı, yoksa `null` |
| `json()` | Çözülmüş gövde; JSON değilse ham metin |
| `array()`, `list()` | Dizi olarak gövde / dizi satırlar |
| `get('nokta.yolu')` | Gövdeden değer |
| `message()` | Sağlayıcı mesajı |
| `toIlluminateResponse()` | Laravel yanıt nesnesi |

## Yeni ElektraWeb modülü

Her modül `Aenzenith\ElektraWeb\Contracts\Module` arayüzünü uygular ve bir ad döndürür. Ad, config
anahtarı ve yönetici kaydı olarak kullanılır.

```php
namespace App\ElektraWeb;

use Aenzenith\ElektraWeb\Contracts\Module;

final class PmsApi implements Module
{
    public static function name(): string
    {
        return 'pms';
    }
}
```

Kayıt, bir servis sağlayıcının `boot()` metodunda yapılır:

```php
use Aenzenith\ElektraWeb\Facades\ElektraWeb;

ElektraWeb::extend('pms', PmsApi::class);
ElektraWeb::extend('pms', fn ($app) => new PmsApi(/* ... */));

ElektraWeb::module('pms');
ElektraWeb::has('pms');
ElektraWeb::modules();   // ['booking_api', 'pms']
```

Modüller ilk erişimde container'dan çözülür ve aynı yönetici içinde tekrar kullanılır.
Kayıtlı olmayan bir ad `UnknownModuleException` atar.
