# Hatalar ve olaylar

## İstisna hiyerarşisi

```
Aenzenith\ElektraWeb\Exceptions\ElektraWebException
├── UnknownModuleException
└── BookingApi\Exceptions\BookingApiException
    ├── NotConfiguredException
    ├── InvalidRequestException
    ├── ConnectionFailedException
    ├── InvalidResponseException
    └── RequestFailedException
        └── AuthenticationException
```

| İstisna | Atıldığı durum |
| --- | --- |
| `NotConfiguredException` | Otel kimliği, API anahtarı ya da captcha eksik |
| `InvalidRequestException` | İstek nesnesi geçersiz; HTTP çağrısı yapılmaz |
| `ConnectionFailedException` | DNS, TLS ya da zaman aşımı; yeniden denemeler tükendi |
| `RequestFailedException` | 2xx dışı yanıt ya da yazma uçlarında `"success": false` |
| `AuthenticationException` | `POST /login` reddedildi ya da yanıtta token yok |
| `InvalidResponseException` | 2xx yanıt beklenen biçimde değil (ör. liste yerine nesne) |

`RequestFailedException`:

```php
try {
    BookingApi::hotel()->reservations()->create($reservationRequest);
} catch (RequestFailedException $exception) {
    $exception->status();            // HTTP durum kodu
    $exception->providerMessage();   // sağlayıcının mesajı, yoksa null
    $exception->isClientError();
    $exception->isServerError();
    $exception->response->json();    // ham yanıt
}
```

Sağlayıcı mesajı şu sırayla aranır: `errorDetails` içindeki ilk metin (alan yoluyla birlikte),
`message`, `error`, `detail`, `title`, `errors` içindeki ilk metin.

## Yeniden deneme

Bağlantı hataları ve 5xx yanıtlar `http.retry.times` kadar ek denemeyle tekrarlanır. 4xx yanıtlar
tekrarlanmaz. `401` yalnız bir kez, yeniden girişten sonra tekrarlanır.

## Olaylar

| Olay | Zaman | Alanlar |
| --- | --- | --- |
| `TokenIssued` | Başarılı girişten sonra | `token`, `credentials` |
| `RequestFailed` | Bir `BookingApiException` atılmadan hemen önce | `method`, `uri`, `exception` |
| `StaleCacheServed` | Bayat önbellek kopyası döndürüldüğünde | `key`, `cause` |

Sağlayıcı hatalarını tek noktadan kaydetmek için:

```php
use Aenzenith\ElektraWeb\BookingApi\Events\RequestFailed;

Event::listen(function (RequestFailed $event) {
    Log::channel('elektraweb')->error($event->exception->getMessage(), [
        'method' => $event->method,
        'uri' => $event->uri,
    ]);
});
```

Laravel HTTP client'ın `RequestSending` ve `ResponseReceived` olayları da her istek için yayınlanır.
