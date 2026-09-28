# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and releases are cut
by release-please from Conventional Commits.

## [1.0.1](https://github.com/aenzenith/laravel-elektraweb/compare/v1.0.0...v1.0.1) (2026-09-28)

### Bug Fixes

* **booking:** payment-type değerleri ElektraWeb desteğine göre düzeltildi ([76ec3f5](https://github.com/aenzenith/laravel-elektraweb/commit/76ec3f5))
  * `PaymentType` API dokümanına göre `NotPaid = 2` / `Paid = 3` olarak yazılmıştı. ElektraWeb desteğinden
    alınan bilgiyle alanın ödeme durumunu değil, rezervasyon kartındaki ödeme yöntemini tuttuğu öğrenildi.
  * Yeni değerler: `CityLedger = 0`, `Cash = 1`, `CreditCard = 2`, `BankTransfer = 3`, `PayByLink = 4`, `Crypto = 5`.
  * `NotPaid` ve `Paid` kaldırıldı. Eski `NotPaid` (2) ile aynı değeri göndermek için `CreditCard`, eski `Paid` (3)
    için `BankTransfer` kullanılır; ancak anlamları farklı olduğu için doğru yöntem seçilmelidir.
  * `withPaymentType()` artık `UpdateReservation` üzerinde de kullanılabilir.
  * `PaymentType::promptsGuestToPay()` eklendi: ElektraWeb'in onay e-postasında misafirden ödeme isteyip
    istemediğini söyler (`BankTransfer`, `PayByLink`).

### Documentation

* Dokümantasyon Türkçeye çevrildi ve `docs/` klasörüne bölündü ([484f872](https://github.com/aenzenith/laravel-elektraweb/commit/484f872))

## 1.0.0 (2026-09-23)

İlk sürüm.
