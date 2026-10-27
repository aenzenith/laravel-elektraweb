# Folyo

Folyo uçları rezervasyonun folyosuna tahsilat ve gelir yazar, folyodaki satırları okur. Hepsi
`hotel()->folio()` altındadır.

| Metot | Uç |
| --- | --- |
| `addPayment(FolioPayment)` | `POST /hotel/{id}/folio/add-payment` |
| `addRevenue(FolioRevenue)` | `POST /hotel/{id}/folio/add-revenue` |
| `entries(int ...$reservationIds)` | `GET /hotel/{id}/folio/details` |
| `definitions(int $type)` | `GET /hotel/{id}/folio/definitions` |

## Tahsilat

ElektraWeb dışında alınan bir ödemeyi (ör. kendi sanal POS'unuzdan) folyoya yazar.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioPayment;
use Aenzenith\ElektraWeb\Facades\BookingApi;

$transaction = BookingApi::hotel()->folio()->addPayment(
    FolioPayment::make(
        reservationId: 95989318,
        departmentId: 789,
        amount: 250.00,
        currency: 'EUR',
        localAmount: 13874.92,
    )
);

$transaction->transactionId; // 470133231
$transaction->message;       // "SP_RES_MAKE_PAYMENT_SUCCESS"
```

| Parametre | Alan | Açıklama |
| --- | --- | --- |
| `reservationId` | `reservation-id` | ElektraWeb rezervasyon numarası |
| `departmentId` | `department-id` | Tahsilatın yazılacağı ödeme departmanı (ör. kredi kartı) |
| `amount` | `amount` | Tahsil edilen tutar |
| `currency` | `currency-code` | `amount` değerinin para birimi |
| `localAmount` | `local-amount` | Aynı tutarın otelin yerel para birimindeki karşılığı |

Tutarlar iki haneye yuvarlanır. Sıfır tutar, işaretleri farklı `amount` / `localAmount` ya da üç harfli
olmayan para birimi kodu istek gönderilmeden `InvalidRequestException` fırlatır. `"success": false`
yanıtı `RequestFailedException` olur.

## Gelir

Folyoya bir satış (ör. restoran harcaması) yazar. Gövde tahsilatla aynıdır, ek olarak gelir kalemi
(`revenue-id`) taşır.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\FolioRevenue;

BookingApi::hotel()->folio()->addRevenue(
    FolioRevenue::make(
        reservationId: 95989318,
        departmentId: 123,
        revenueId: 456,
        amount: 1000.00,
        currency: 'TRY',
        localAmount: 1000.00,
    )
);
```

## Yerel para tutarı

Otelin yerel para birimi, `exchange-rate` yanıtında `1` değerini alan para birimidir. Diğer para
birimlerinin kuru, bir birim yerel paranın o para birimindeki karşılığıdır.

```php
$rate = BookingApi::hotel()->definitions()->exchangeRate('EUR');

$localAmount = $rate?->toLocal(250.00); // 13874.92
```

Otel para birimini yayınlamıyorsa `exchangeRate()`, kur sıfır ya da boşsa `toLocal()` null döner.

## Folyoyu okuma

```php
use Aenzenith\ElektraWeb\BookingApi\Data\FolioEntry;

$entries = BookingApi::hotel()->folio()->entries(95989318);

foreach ($entries as $entry) {
    $entry->departmentName; // "CityLedger"
    $entry->amount;         // -2.0
}
```

Birden fazla rezervasyon numarası `reservation-ids` parametresinde virgülle birleştirilerek gönderilir.
Yanıttaki sütun adları büyük harflidir; `FolioEntry` şu sütunları eşler:

| Özellik | Sütun |
| --- | --- |
| `id` | `ID` |
| `reservationId` | `RESID` |
| `departmentId` / `departmentName` | `DEPID` / `DEPARTMENTNAME` |
| `revenueId` / `revenueName` | `REVID` / `REVENUENAME` |
| `amount` | `CTOTAL` |
| `localAmount` | `MCTOTAL` |
| `currency` / `rate` | `CURCODE` / `CURRATE` |
| `note` | `NOTE` |
| `transactionDate` / `createdAt` | `TDATE` / `CREATION_DATE` |
| `invoiceId` / `documentNo` | `INVOICEID` / `DOCNO` |
| `guestName` / `userCode` | `FULLNAME` / `USERCODE` |

Diğer sütunlara `$entry->get('VAT1AMOUNT')` ya da `$entry->raw` ile erişilir. Dokümandaki örnekte
tahsilat ve indirim satırları eksi, gelir satırları artı tutarla gelir.

## Tanımlar

```php
$boardTypes = BookingApi::hotel()->folio()->definitions(7);
```

Dokümanda yalnız `7` (pansiyon tipleri) örneklenir. Satır yapısı tipe göre değiştiği için satırlar
yanıttaki haliyle dizi olarak döner ve `folio_definitions` kovasında 6 saat önbellekte tutulur.
Tahsilat ve gelir için gereken departman ve gelir kalemi numaraları ElektraWeb'den öğrenilir.

## Tekrar gönderim

Folyo yazımları idempotent değildir ve paket POST isteklerini tekrar denemez
([Yeniden deneme](10-hatalar-ve-olaylar.md#yeniden-deneme)). Zaman aşımına uğrayan bir tahsilat
ElektraWeb'e ulaşmış olabilir. Aynı tutarı yeniden göndermeden önce folyoyu kontrol edin:

```php
$alreadyPosted = BookingApi::hotel()->folio()->entries($reservationId)->contains(
    fn (FolioEntry $entry) => $entry->departmentId === $departmentId
        && abs(abs($entry->amount) - $amount) < 0.01
);
```

Sanal POS akışında tahsilatın hangi adımda yazılacağı [Ödeme tipi](05-odeme-tipi.md#kendi-sanal-posunuzla-ödeme)
sayfasında anlatılır.
