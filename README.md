# Laravel ElektraWeb

[![Tests](https://github.com/aenzenith/laravel-elektraweb/actions/workflows/tests.yml/badge.svg)](https://github.com/aenzenith/laravel-elektraweb/actions/workflows/tests.yml)

[ElektraWeb](https://www.elektraweb.com/) API'leri için Laravel istemcisi. Şu an
[Booking API](https://hotel.docs.bookingapi.elektraweb.com/) modülünü içerir.

Booking API dokümanındaki tüm uçlar desteklenir: fiyat ve müsaitlik, rezervasyon oluşturma, güncelleme
ve iptal, ekstra hizmet rezervasyonu, misafirin SMS ile iptali, otel tanımları, B2E listeleri ve sistem
sabitleri. İstekler Laravel HTTP client üzerinden gider, JWT önbellekte tutulur, referans veriler
sağlayıcı erişilemezken önbellekteki son kopyadan sunulur.

| Gereksinim | Sürüm |
| --- | --- |
| PHP | 8.1+ |
| Laravel | 9, 10, 11, 12, 13 |

## Kurulum

```bash
composer require aenzenith/laravel-elektraweb
php artisan vendor:publish --tag=elektraweb-config
```

```dotenv
ELEKTRAWEB_HOTEL_ID=26780
ELEKTRAWEB_BOOKING_API_KEY=api-anahtariniz
```

## Kullanım

```php
use Aenzenith\ElektraWeb\BookingApi\Enums\PaymentType;
use Aenzenith\ElektraWeb\BookingApi\Requests\Contact;
use Aenzenith\ElektraWeb\BookingApi\Requests\CreateReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\Guest;
use Aenzenith\ElektraWeb\BookingApi\Requests\PriceSearch;
use Aenzenith\ElektraWeb\Facades\BookingApi;

$hotel = BookingApi::hotel();

$offer = $hotel->rates()
    ->search(PriceSearch::make('2026-07-01', '2026-07-05', adults: 2)->withCurrency('EUR'))
    ->bookable()
    ->cheapest();

$reservation = $hotel->reservations()->create(
    CreateReservation::fromOffer($offer, '2026-07-01', '2026-07-05', nationality: 'TR')
        ->withGuests([Guest::mr('Ahmet', 'Yılmaz'), Guest::ms('Ayşe', 'Yılmaz')])
        ->withContact(Contact::make('Ahmet', 'Yılmaz', 'ahmet.yilmaz@ornek.com', '+90 532 111 22 33'))
        ->withPaymentType(PaymentType::BankTransfer)
);

$reservation->reservationId;
```

`payment-type` alanı rezervasyonun ödeme yöntemini belirtir. Değerler ve kendi sanal POS'unuzla ödeme
alırken izlenecek sıra [Ödeme tipi](docs/05-odeme-tipi.md) sayfasında anlatılır.

## Dokümantasyon

Tam dokümantasyona [`docs/`](docs/README.md) klasöründen ulaşabilirsiniz.

- [Kurulum ve yapılandırma](docs/01-kurulum-ve-yapilandirma.md)
- [Kimlik doğrulama](docs/02-kimlik-dogrulama.md)
- [Fiyat ve müsaitlik](docs/03-fiyat-ve-musaitlik.md)
- [Rezervasyon işlemleri](docs/04-rezervasyon.md)
- [Ödeme tipi](docs/05-odeme-tipi.md)
- [Misafirin SMS ile iptali](docs/06-misafir-iptali.md)
- [Otel tanımları ve sabit listeler](docs/07-tanimlar-ve-sabit-listeler.md)
- [B2E işlemleri](docs/08-b2e-islemleri.md)
- [Önbellek](docs/09-onbellek.md)
- [Hatalar ve olaylar](docs/10-hatalar-ve-olaylar.md)
- [Test](docs/11-test.md)
- [Düşük seviyeli erişim ve yeni modüller](docs/12-dusuk-seviye-ve-moduller.md)

## Geliştirme

```bash
composer test       # PHPUnit
composer analyse    # PHPStan, seviye 8
composer format     # Pint
```

CI, PHP 8.1–8.5 ve Laravel 9–13 kombinasyonlarını `prefer-lowest` ve `prefer-stable` bağımlılıklarla çalıştırır.

## Lisans

MIT. Ayrıntılar için [LICENSE](LICENSE).
