# Otel tanımları ve sabit listeler

Bu uçların yanıtları önbelleğe alınır; bkz. [Önbellek](09-onbellek.md).

## Otel tanımları

`GET /hotel/{id}/hotel-definitions`: oda tipleri, pansiyon tipleri ve fiyat tipleri.

```php
$definitions = BookingApi::hotel()->definitions()->all('TR');

foreach ($definitions->roomTypes as $room) {
    $room->name;                  // Standart
    $room->code;                  // STD
    $room->area;                  // 80.0
    $room->rules->maxAdults;      // 3
    $room->plainDescription();    // HTML temizlenmiş açıklama
    $room->imageUrl;
}

$definitions->roomType(401038);
$definitions->boardType(46820);
$definitions->rateType(26769)?->isRefundable();
```

Arama sonuçlarındaki `roomTypeId` değeri `roomType()` ile eşleştirilerek teklif kartlarına oda
bilgisi eklenir.

## Parametreler

`GET /hotel/{id}/params`: rezervasyon motoru ayarları. Yanıt çok sayıda bölüm içerdiğinden sık
kullanılan değerler metotla, diğerleri nokta yoluyla okunur.

```php
$params = BookingApi::hotel()->definitions()->params();

$params->hotelName();
$params->defaultCurrency();          // EUR
$params->currencies();               // ['EUR', 'TRY', 'USD']
$params->languages();
$params->supportsCurrency('TRY');
$params->maxAdults();
$params->maxChildAge();
$params->minLengthOfStay();
$params->paysByWireTransfer();
$params->bankInformation();

$params->get('reservation-form-settings.birthdate-field-required');
$params->section('payment');
```

`params($roomTypeGroupId)` belirli bir oda grubu için parametreleri getirir.

## Döviz kurları

```php
foreach (BookingApi::hotel()->definitions()->exchangeRates() as $rate) {
    $rate->currency;
    $rate->rate;
}
```

## Ekstra hizmetler

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\ExtraServicesQuery;

$services = BookingApi::hotel()->definitions()->extraServices(
    ExtraServicesQuery::make()
        ->withLanguage('TR')
        ->withCurrency('TRY')
        ->sellWithoutReservation(false)
);
```

Kurlar ve ekstra hizmetler için dokümanda örnek yanıt bulunmadığından ilgili sınıflar alanları birkaç
olası adla arar. Tanınmayan alanlara `raw` ile erişilir.

## Sabit listeler

Otelden bağımsız sistem listeleri:

```php
$constants = BookingApi::constants();

$constants->countries();            // Collection<Country>
$constants->country('TR')?->name;   // Turkey
$constants->cities();               // Collection<City>
$constants->standardBoardTypes();   // Collection<StandardBoardType>
```
