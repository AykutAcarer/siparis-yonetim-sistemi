# Siparis Yonetim Dashboard

Laravel 11 + Vue 3 tabanli bu uygulama, Google Sheets uzerindeki "Completed Orders" ve "Abandoned" sayfalarini okuyarak lokal ortamda calisan yetkisiz bir siparis yonetim paneli saglar. Panel uzerinden siparisler filtrelenebilir, aranabilir ve dispatch webhook'una gonderilebilir.

## Ozellikler

- Google Sheets API veya otomatik mock fallback ile tamamlanan ve yarim kalan siparisleri listeleme
- Telegram / Whatsapp / Voice kanallarina gore ayristirilmis gorunum ve Completed / Abandoned sekmeleri
- Whatsapp kanali icin ayri bir Google Spreadsheet baglantisini kullanma destegi
- Arama, tarih araligi, odeme tipi ve durum filtreleri; tarih ve tutar siralama, 25/50/100 sayfalama secenekleri
- "Gonderildi" butonu ile belirtilen webhook'a POST istegi, 2 deneme ile exponential backoff ve toast bildirimleri
- Basari durumunda satir statusunun Dispatched olarak isaretlenmesi ve local dosyada saklanmasi
- 60 saniyede otomatik yenileme ve manuel "Yenile" butonu
- API icin 30 istek/dakika rate limit ve yalnizca localhost CORS yetkisi

## Kurulum

### Gereksinimler

- PHP 8.2+
- Composer
- Node.js 18+
- npm (veya pnpm/yarn)

### Adimlar

1. Depoyu klonlayin veya mevcut klasorde calismaya baslayin.
2. Ortam dosyasini olusturun ve anahtari uretin:

   ```bash
   cp .env.example .env
   composer install
   php artisan key:generate
   ```

3. Google Sheets erisimi icin `.env` uzerinde asagidaki degerleri doldurun:

   ```env
   GOOGLE_SHEETS_DEFAULT_CHANNEL=telegram
   GOOGLE_APPLICATION_CREDENTIALS=/absolute/path/to/service_account.json

   # Telegram (varsayilan kanal)
   GOOGLE_SHEETS_SPREADSHEET_ID_TELEGRAM=<TELEGRAM_SHEET_ID>
   GOOGLE_SHEETS_TELEGRAM_COMPLETED_RANGE="Completed Orders!A1:Z9999"
   GOOGLE_SHEETS_TELEGRAM_ABANDONED_RANGE="Abandoned!A1:Z9999"

   # Whatsapp
   GOOGLE_SHEETS_SPREADSHEET_ID_WHATSAPP=1_8eXpSO0kex3kO2Jx77EYTA8WZ7GmIB1pK4TwstsUAk
   GOOGLE_SHEETS_WHATSAPP_COMPLETED_RANGE="Completed Orders!A1:Z9999"
   GOOGLE_SHEETS_WHATSAPP_ABANDONED_RANGE="Abandoned!A1:Z9999"

   # Voice (opsiyonel, ayarlanana kadar varsayilan kanala geri duser)
   GOOGLE_SHEETS_SPREADSHEET_ID_VOICE=
   GOOGLE_SHEETS_VOICE_COMPLETED_RANGE=
   GOOGLE_SHEETS_VOICE_ABANDONED_RANGE=

   WEBHOOK_DISPATCH_URL="https://technai.app.n8n.cloud/webhook/dispatch-order"
   WEBHOOK_DISPATCH_CHAT_ID=7948113920
   ```

   > Not: Google service account JSON dosyasi belirtilen yolda bulunmalidir. Dosya yoksa veya erisilemiyorsa backend otomatik olarak `storage/app/mock/*.json` icerigine geri doner.

4. Frontend bagimliliklarini kurun ve Vite gelistirme sunucusunu baslatin:

   ```bash
   npm install
   npm run dev
   ```

5. Laravel gelistirme sunucusunu calistirin:

   ```bash
   php artisan serve
   ```

6. Tarayicidan `http://localhost:8000` adresine gidin. Giris gerektirmeden Vue bazli dashboard yuklenecektir.

## Testler

- Backend feature testleri icin:

  ```bash
  php artisan test
  ```

- Frontend icin ayrica `npm run lint` veya `npm run build` calistirarak derleme kontrolleri yapabilirsiniz.

## Mimari Notlar

- `App\Services\Orders\OrderDataService` Google Sheets uzerinden veri cekmeyi dener, hata durumunda `MockOrderRepository` uzerinden `storage/app/mock` dizinindeki JSON dosyalarini kullanir.
- Dispatch islemleri `App\Services\Orders\DispatchOrderService` tarafindan yonetilir ve her basarili islem `storage/app/dispatches.json` dosyasina yazilarak UI tekrar acildiginda da status korunur.
- Vue uygulamasi `resources/js/App.vue` icinde olusturulan layout uzerinden `Sidebar`, `OrdersTabs`, `CompletedOrdersTable` ve `AbandonedOrdersTable` komponentlerini kullanir.

## Komut Ozeti

| Komut | Aciklama |
| --- | --- |
| `composer install` | PHP bagimliliklarini kurar |
| `npm install` | JS bagimliliklarini kurar |
| `npm run dev` | Vite gelistirme sunucusunu baslatir |
| `php artisan serve` | Laravel HTTP sunucusunu baslatir |
| `php artisan test` | PHPUnit testlerini calistirir |

## Fallback Mock Verileri

- `storage/app/mock/completed.json`
- `storage/app/mock/abandoned.json`

Bu dosyalar yoksa olusturabilir ve gerekli sahte verilerle doldurabilirsiniz. Google Sheets erisimi saglandiginda otomatik olarak gercek veriler kullanilir.

---

Sorular icin proje notlarinda belirtilen degisiklikleri inceleyebilirsiniz.
