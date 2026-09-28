# Ödeme tipi

`payment-type`, ElektraWeb rezervasyon kartında görünen ödeme yöntemidir.

| `PaymentType` | Değer | ElektraWeb davranışı |
| --- | --- | --- |
| `CityLedger` | 0 | Cari hesap |
| `Cash` | 1 | Nakit |
| `CreditCard` | 2 | Kredi kartı |
| `BankTransfer` | 3 | Otelin banka bilgileri ElektraWeb'de tanımlıysa onay e-postasına eklenir |
| `PayByLink` | 4 | Misafire ödeme linki gönderilir |
| `Crypto` | 5 | Kripto |

Değer hem `CreateReservation` hem `UpdateReservation` ile gönderilebilir. Güncelleme, karttaki ödeme
yöntemini değiştirir. `PaymentType::promptsGuestToPay()`, ElektraWeb'in onay e-postasında misafirden
ödeme isteyip istemediğini bildirir (`BankTransfer`, `PayByLink`).

## Kendi sanal POS'unuzla ödeme

Booking API'de ön provizyon, ödeme kaydı ya da ödeme durumunu bildiren webhook yoktur. ElektraWeb'in
kendi rezervasyon motoru önce ödemeyi alır, ödeme başarısızsa rezervasyon oluşturmaz. Kendi POS'unuzu
kullanırken aynı sıra önerilir:

1. Kartta ön provizyon alın.
2. Teklifi yeniden arayın; fiyatın ve müsaitliğin değişmediğini doğrulayın.
3. `create()` çağrısını `PaymentType::CreditCard` ile yapın.
4. Rezervasyon oluştuysa provizyonu tahsilata çevirin; oluşmadıysa provizyonu iptal edin.

```php
$preAuth = $pos->preAuthorize($order);

$current = BookingApi::hotel()->rates()->search($order->search())->equivalentTo($order->offer());

if ($current === null || ! $current->isBookable()) {
    $pos->void($preAuth);

    return;
}

try {
    $reservation = BookingApi::hotel()->reservations()->create(
        $order->reservationRequest($current)->withPaymentType(PaymentType::CreditCard)
    );
} catch (ElektraWebException $exception) {
    $pos->void($preAuth);

    throw $exception;
}

$pos->capture($preAuth);
```

`$pos` ve `$order` uygulamanıza aittir; paket yalnız ElektraWeb tarafını yönetir.

## Ödemesiz yöntemler

| Sitedeki seçim | Önerilen değer |
| --- | --- |
| Havale / EFT | `BankTransfer` |
| Otelde ödeme | `Cash` |
| Ödeme linkini ElektraWeb göndersin | `PayByLink` |
| Kurumsal / acente | `CityLedger` |
