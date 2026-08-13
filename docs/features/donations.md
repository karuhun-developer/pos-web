# Fitur — Donasi

**Status:** ✅ Selesai (branch `feat/web-ui`)

## Tujuan
POS Pro gratis dipakai; donasi murni sukarela. Halaman publik `/dukung` menampung tiga
kanal, dan panel superadmin `/admin/donasi` merekapnya.

**Donasi tidak membuka fitur apa pun.** Itu keputusan sadar — karena tidak ada yang bisa
dibuka, transfer manual tidak perlu diverifikasi dan tidak ada yang bisa dicurangi
dengan mengaku sudah transfer.

## Tiga kanal

| Kanal | Alur | Status awal |
|---|---|---|
| `manual` | tampilkan rekening/QRIS statis, **catat saja tanpa verifikasi** | `recorded` |
| `paywuz` | buat transaksi hosted-checkout → donatur diarahkan ke halaman bayar | `pending` → `paid` lewat webhook |
| `external` | tombol keluar ke Saweria/Trakteer | tidak dicatat sama sekali |

Kanal yang datanya belum diisi di `.env` **otomatis tidak muncul** di halaman — tidak
ada tombol yang mengarah ke rekening kosong. Konfigurasinya di `config/donation.php`
(preset nominal, min/max, rekening manual, kedaluwarsa checkout, tautan eksternal,
batas dinding donatur).

## Data
Tabel `donations` — **bukan entity sync**: ini tabel platform, bukan milik toko, jadi
PK auto-increment dan timestamp ISO biasa (bukan UUID + epoch ms).

Kolom penting: `user_id` (nullable, boleh anonim), `donor_name`, `donor_email`,
`amount` (**integer rupiah**, konsisten dengan uang di app ini), `message`, `channel`,
`status` (`recorded|pending|paid|expired|cancelled`), `order_id` (unik), `reference`,
`payment_method`, `redirect_url`, `paid_at`, `raw_response`, `raw_webhook`,
`is_anonymous`.

`order_id` = `DON-{ymd}-{12 karakter acak}`. Bagian acaknya panjang karena `order_id`
sekaligus jadi route key halaman terima kasih — id berurut akan membuat pesan donatur
lain bisa diintip dengan menaikkan angka.

## Paywuz
`app/Services/PaywuzClient.php` — pembungkus tipis REST API-nya di atas `Http` client
Laravel (bukan curl mentah) supaya bisa di-`Http::fake()` di test.

`CreateDonationCheckout` membuat **baris donasi lebih dulu** (status `pending`), baru
transaksinya. Urutan ini penting: kalau transaksi dibuat duluan lalu penyimpanan gagal,
webhook akan datang membawa order id yang tidak dikenal dan uangnya jadi tidak tercatat.
Kalau Paywuz menolak atau tidak mengembalikan `paymentUrl`, baris tadi diubah jadi
`cancelled` supaya tidak menggantung sebagai `pending` selamanya.

### Webhook
`POST /api/v1/webhooks/paywuz` (`PaywuzWebhookController`, route lewat atribut → di luar
grup `web`, jadi bebas CSRF).

- Header `X-Paywuz-Signature: sha256=<hmac>` dibandingkan dengan
  `hash_hmac('sha256', body, config('services.paywuz.webhook_secret'))` memakai
  **`hash_equals`** (waktu-tetap, supaya tanda tangan tidak bisa ditebak byte demi byte
  lewat selisih waktu respons). Tidak cocok / secret kosong → **`401`**.
- Setelah tanda tangan sah, jawabannya **selalu `200`** — payload tak dikenal cukup
  dicatat di log; membalas error hanya memicu kiriman ulang tanpa akhir.
- `HandlePaywuzWebhook` **idempoten**: donasi yang sudah `paid` tidak pernah diubah lagi,
  jadi kiriman ganda tidak menggeser `paid_at` maupun menggandakan apa pun. Event
  `expired`/`cancelled` juga tidak pernah membatalkan uang yang sudah masuk.

Event yang dikenali: `transaction.paid`, `transaction.settlement` → `paid`;
`transaction.expired` → `expired`; `transaction.cancelled` → `cancelled`.

## Halaman
- **`/dukung`** — nominal preset + custom (dibatasi `DONATION_MIN`/`DONATION_MAX`),
  nama/pesan, opsi anonim, pilih kanal. Ada **dinding donatur**: donasi anonim tetap
  tampil dengan nama disamarkan supaya jumlah dukungan terlihat apa adanya; email tidak
  pernah ikut keluar. POST-nya dibatasi `throttle:10,1` karena bisa ditulis tanpa akun.
- **`/dukung/selesai/{donation}`** — halaman terima kasih; untuk kanal manual berisi
  instruksi transfernya.
- **`/admin/donasi`** — filter (cari/kanal/status/tanggal) sebagai satu sumber
  kebenaran: tabel, total, chart per bulan, dan tautan ekspor CSV semuanya memakai query
  yang sama, jadi angka di layar selalu cocok dengan berkas yang terunduh. Ada tombol
  ubah status manual (mis. tandai lunas setelah transfernya dicek sendiri).

## Env

```
PAYWUZ_BASE_URL=      PAYWUZ_KEY=      PAYWUZ_WEBHOOK_SECRET=
DONATION_BANK_NAME=   DONATION_BANK_ACCOUNT=   DONATION_BANK_HOLDER=   DONATION_QRIS_URL=
DONATION_SAWERIA_URL= DONATION_TRAKTEER_URL=
DONATION_MIN=5000     DONATION_MAX=50000000    DONATION_EXPIRY_MINUTES=60
```

## Kode
- `app/Models/Donation.php`, migration `donations`.
- `app/Actions/Donation/{RecordDonation,CreateDonationCheckout,HandlePaywuzWebhook,UpdateDonationStatus}.php`.
- `app/Services/PaywuzClient.php`, `app/Exceptions/PaywuzException.php`.
- `app/Http/Controllers/Web/DonationController.php`,
  `app/Http/Controllers/Web/Admin/AdminDonationController.php`,
  `app/Http/Controllers/Api/V1/PaywuzWebhookController.php`.
- `resources/js/Pages/Donate/{Index,Thanks}.vue`, `resources/js/Pages/Admin/Donations/Index.vue`,
  `resources/js/lib/donation.ts` (label kanal/status).
- Test: `tests/Feature/Web/DonationTest.php` (9 kasus, termasuk signature salah → 401,
  `transaction.paid` → `paid`, dan kiriman ganda tidak menggandakan).
