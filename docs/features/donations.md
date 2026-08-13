# Fitur — Donasi

**Status:** ✅ Selesai (branch `feat/web-ui`)

## Tujuan
POS Pro gratis dipakai; donasi murni sukarela. Halaman publik `/dukung` menampung
kanalnya, dan panel superadmin `/admin/donasi` merekapnya.

**Donasi tidak membuka fitur apa pun.** Itu keputusan sadar — karena tidak ada yang bisa
dibuka, transfer manual tidak perlu diverifikasi dan tidak ada yang bisa dicurangi
dengan mengaku sudah transfer.

## Kanal

| Kanal | Alur | Status awal |
|---|---|---|
| `manual` | tampilkan rekening/QRIS statis, **catat saja tanpa verifikasi** | `recorded` |
| `external` | tombol keluar ke Saweria/Trakteer | tidak dicatat sama sekali |

Kanal yang datanya belum diisi di `.env` **otomatis tidak muncul** di halaman — tidak
ada tombol yang mengarah ke rekening kosong. Konfigurasinya di `config/donation.php`
(preset nominal, min/max, rekening manual, tautan eksternal, batas dinding donatur).

## Data
Tabel `donations` — **bukan entity sync**: ini tabel platform, bukan milik toko, jadi
PK auto-increment dan timestamp ISO biasa (bukan UUID + epoch ms).

Kolom penting: `user_id` (nullable, boleh anonim), `donor_name`, `donor_email`,
`amount` (**integer rupiah**, konsisten dengan uang di app ini), `message`, `channel`,
`status` (`recorded|paid|cancelled`), `order_id` (unik), `paid_at`, `is_anonymous`.

`order_id` = `DON-{ymd}-{12 karakter acak}`. Bagian acaknya panjang karena `order_id`
sekaligus jadi route key halaman terima kasih — id berurut akan membuat pesan donatur
lain bisa diintip dengan menaikkan angka.

## Halaman
- **`/dukung`** — nominal preset + custom (dibatasi `DONATION_MIN`/`DONATION_MAX`),
  nama/pesan, opsi anonim. Ada **dinding donatur**: donasi anonim tetap
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
DONATION_BANK_NAME=   DONATION_BANK_ACCOUNT=   DONATION_BANK_HOLDER=   DONATION_QRIS_URL=
DONATION_SAWERIA_URL= DONATION_TRAKTEER_URL=
DONATION_MIN=5000     DONATION_MAX=50000000
```

## Kode
- `app/Models/Donation.php`, migration `donations`.
- `app/Actions/Donation/{RecordDonation,UpdateDonationStatus}.php`.
- `app/Http/Controllers/Web/DonationController.php`,
  `app/Http/Controllers/Web/Admin/AdminDonationController.php`.
- `resources/js/Pages/Donate/{Index,Thanks}.vue`, `resources/js/Pages/Admin/Donations/Index.vue`,
  `resources/js/lib/donation.ts` (label kanal/status).
- Test: `tests/Feature/Web/DonationTest.php`.
