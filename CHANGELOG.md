# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and releases are cut
by release-please from Conventional Commits.

## [1.2.1](https://github.com/aenzenith/laravel-elektraweb/compare/v1.2.0...v1.2.1) (2026-10-27)


### Bug Fixes

* en düşük PHP sürümü 8.2'ye yükseltildi ([e54f325](https://github.com/aenzenith/laravel-elektraweb/commit/e54f3259b3a55e20df0261dcedb7846a0e9e3279))

## [1.2.0](https://github.com/aenzenith/laravel-elektraweb/compare/v1.0.1...v1.2.0) (2026-10-27)

1.1.0 olarak hazırlanan sürüm yayımlanmadı; içeriği bu sürümdedir.

### Features

* **booking:** folyo uçları eklendi ([df1478e](https://github.com/aenzenith/laravel-elektraweb/commit/df1478e))
  * `hotel()->folio()->addPayment(FolioPayment)`: `POST /hotel/{id}/folio/add-payment`, folyoya tahsilat yazar.
  * `hotel()->folio()->addRevenue(FolioRevenue)`: `POST /hotel/{id}/folio/add-revenue`, folyoya gelir yazar.
  * `hotel()->folio()->entries(int ...$reservationIds)`: `GET /hotel/{id}/folio/details`, satırları `FolioEntry` olarak döndürür.
  * `hotel()->folio()->definitions(int $type)`: `GET /hotel/{id}/folio/definitions`, `folio_definitions` kovasında önbelleğe alınır.
* **booking:** `definitions()->exchangeRate(string $currency)`, `ExchangeRate::toLocal()` ve `ExchangeRate::fromLocal()` eklendi ([4b04087](https://github.com/aenzenith/laravel-elektraweb/commit/4b04087))

### Bug Fixes

* **booking:** `exchangeRates()` uç yanıtındaki düz nesneyi (`{"rates-date": ..., "EUR": 0.018, "TRY": 1}`) okuyamıyor ve boş koleksiyon döndürüyordu; her para birimi artık ayrı bir `ExchangeRate` olarak döner ([4b04087](https://github.com/aenzenith/laravel-elektraweb/commit/4b04087))
* **booking:** POST istekleri bağlantı hatası ve 5xx yanıtta tekrar gönderilmiyor. Zaman aşımına uğrayan bir `createReservation` ya da folyo yazımı sağlayıcıda işlenmiş olabileceği için tekrar denemek çift kayıt üretebiliyordu. GET istekleri ve `POST /login` eskisi gibi `http.retry.times` kadar tekrarlanır ([428d37f](https://github.com/aenzenith/laravel-elektraweb/commit/428d37f))

### Documentation

* Folyo sayfası eklendi, sanal POS akışına tahsilat adımı yazıldı ([e0b682a](https://github.com/aenzenith/laravel-elektraweb/commit/e0b682a))

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
