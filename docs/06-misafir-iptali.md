# Misafirin SMS ile iptali

Misafirin kendi rezervasyonunu iptal ettiği iki adımlı akış. Uçlar dokümanda `noauth` olarak
işaretlidir ve reCAPTCHA token'ı bekler. Paket, kimlik bilgisi tanımlıysa JWT'yi de ekler.

## 1. Rezervasyonu bulma ve SMS gönderme

`POST /hotel/{id}/findGuestReservation`. Giriş tarihi zorunludur; soyad, voucher numarası, telefon ya
da rezervasyon numarasından en az biri verilmelidir.

```php
use Aenzenith\ElektraWeb\BookingApi\Requests\GuestReservationLookup;

$cancellation = BookingApi::withCaptcha($captchaToken)->hotel()->guestCancellation();

$result = $cancellation->find(
    GuestReservationLookup::forCheckIn('2026-07-01')
        ->withSurname('Şahin')
        ->withPhoneNumber('+90 533 222 11 00')
);
```

Yanıt iki biçimde gelir:

| Durum | Kontrol | İçerik |
| --- | --- | --- |
| Tek rezervasyon eşleşti, SMS gönderildi | `$result->smsSent()` | `$result->smsCheckUid` |
| Birden fazla rezervasyon eşleşti | `$result->needsSelection()` | `$result->candidates` (`ReservationCandidate`) |

Birden fazla eşleşmede misafir listeden seçim yapar ve istek rezervasyon numarasıyla tekrarlanır:

```php
if ($result->needsSelection()) {
    $selected = $result->candidates->first();   // $selected->reservationNo, guestNames, checkIn, checkOut

    $result = $cancellation->find(
        GuestReservationLookup::forCheckIn('2026-07-01')
            ->withPhoneNumber('+90 533 222 11 00')
            ->withReservationNo($selected->reservationNo)
    );
}
```

## 2. SMS koduyla onay

`POST /hotel/{id}/cancelGuestReservation`.

```php
$confirmation = $cancellation->confirm($result->smsCheckUid, $request->input('sms_code'));
$confirmation->success;   // yanlış kodda RequestFailedException atılır
```
