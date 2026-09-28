# Rezervasyon işlemleri

## Rezervasyon oluşturma

`POST /hotel/{id}/createReservation` gövdesi `CreateReservation` ile kurulur. Önerilen başlangıç noktası
bir arama sonucudur; oda, pansiyon, fiyat tipi, fiyat kodu, acente, pazar, tutar ve para birimi tekliften
kopyalanır.

```php
use Aenzenith\ElektraWeb\BookingApi\Enums\PaymentType;
use Aenzenith\ElektraWeb\BookingApi\Requests\Contact;
use Aenzenith\ElektraWeb\BookingApi\Requests\CreateReservation;
use Aenzenith\ElektraWeb\BookingApi\Requests\Guest;
use Aenzenith\ElektraWeb\BookingApi\Requests\TaxDetails;

$reservationRequest = CreateReservation::fromOffer($offer, '2026-07-01', '2026-07-05', nationality: 'TR')
    ->withGuests([
        Guest::mr('Mehmet', 'Demir')->withNationalNo('12345678901'),
        Guest::ms('Fatma', 'Demir'),
        Guest::child('Can', 'Demir', birthday: '2019-05-14'),
        Guest::baby('Ece', 'Demir', birthday: '2025-11-02'),
    ])
    ->withContact(Contact::make('Mehmet', 'Demir', 'mehmet.demir@ornek.com', '+90 532 000 11 22'))
    ->withNotes('Bebek yatağı talep edildi')
    ->withVoucherNo('WEB-2026-00042')
    ->withTax(TaxDetails::company('Demir Turizm Ltd. Şti.', '1234567890', 'Kadıköy', 'Moda Cd. 1'))
    ->withPaymentType(PaymentType::CreditCard);

$result = BookingApi::hotel()->reservations()->create($reservationRequest);

$result->reservationId;   // int
$result->paymentUrl;      // yanıtta ödeme linki varsa
```

Yanıtta `success` alanı `false` ise ya da `reservation-id` yoksa `RequestFailedException` atılır;
sağlayıcının mesajı `providerMessage()` ile okunur.

### Tekliften bağımsız kurulum

```php
CreateReservation::make()
    ->withRate(rateTypeId: 34882, boardTypeId: 57053, rateCodeId: 685316, roomTypeId: 425897, priceAgencyId: 459)
    ->withPrice(4500.00, 'TRY')
    ->withStay('2026-07-01', '2026-07-05')
    ->withNationality('TR')
    ->withOccupancy(adults: 2);
```

### Misafirler

| Fabrika metodu | `title-id` | Not |
| --- | --- | --- |
| `Guest::mr($name, $surname)` | 0 | Cinsiyet erkek atanır |
| `Guest::ms($name, $surname)` | 1 | Cinsiyet kadın atanır |
| `Guest::child($name, $surname, $birthday)` | 2 | Doğum tarihi zorunlu |
| `Guest::baby($name, $surname, $birthday)` | 3 | Doğum tarihi zorunlu |
| `Guest::adult($name, $surname, ?Gender)` | 0 / 1 | Cinsiyete göre |

Ek alanlar: `withCountry`, `withBirthday`, `withNationalNo`, `withPassportNo`, `withEmail`, `withPhone`,
`withGender`. Telefon numaraları rakam ve baştaki `+` dışında temizlenir; `00` öneki `+` olarak yazılır.

Yetişkin, büyük çocuk ve bebek sayıları `withOccupancy()` verilmezse misafir listesinden hesaplanır.
Misafir listesinde yetişkin yoksa ve `withOccupancy()` da verilmemişse istek gönderilmez.

### Doğrulama

Aşağıdaki durumlarda `InvalidRequestException` HTTP çağrısından önce atılır:

- oda, pansiyon, fiyat tipi, acente, tutar, para birimi, tarih ya da uyruk eksik;
- çıkış tarihi girişten önce ya da aynı gün;
- yetişkin sayısı 1'den küçük;
- çocuk ya da bebek için doğum tarihi yok;
- tutar ya da sayılar negatif.

## Rezervasyon güncelleme

`UpdateReservation`, oluşturma isteğiyle aynı alanları ve ek olarak `reservation-id`,
`agency-commission`, `promo-code`, `use-guest-bonus`, `is-offer` alanlarını taşır.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\UpdateReservation;

BookingApi::hotel()->reservations()->update(
    UpdateReservation::fromOffer(96999662, $offer, '2026-07-02', '2026-07-06', nationality: 'TR')
        ->withOccupancy(adults: 2)
        ->withPaymentType(PaymentType::BankTransfer)
);
```

## Rezervasyona ekstra hizmet ekleme

`POST /hotel/{id}/createServiceReservation`. Toplam tutar verilmezse satırların toplamı gönderilir.
Her satır en fazla dört ek soru-cevap taşır; boş kalanlar `null` olarak gönderilir.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\ServiceLine;
use Aenzenith\ElektraWeb\BookingApi\Requests\ServiceReservation;

BookingApi::hotel()->reservations()->addServices(
    ServiceReservation::for(96999662, 'TRY')
        ->addLine(ServiceLine::make(5879, '2026-07-01', 1500)->withUnits(1))
        ->addLine(
            ServiceLine::make(5875, '2026-07-02', 2400)
                ->withPax(adults: 2)
                ->withAnswer('Karşılama noktası?', 'Dalaman Havalimanı')
        )
);
```

Satılabilir hizmetlerin listesi için [Otel tanımları](07-tanimlar-ve-sabit-listeler.md#ekstra-hizmetler)
sayfasına bakın.

## İptal

B2E kapsamındaki `cancel-reservation` ucu:

```php
$result = BookingApi::hotel()->reservations()->cancel(96999662);
$result->success;
$result->message;
```

Misafirin kendi rezervasyonunu SMS doğrulamasıyla iptal etmesi ayrı bir akıştır:
[Misafirin SMS ile iptali](06-misafir-iptali.md).
