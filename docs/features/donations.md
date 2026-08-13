# Fitur — Donasi

**Status:** ✅ Selesai (branch `feat/web-ui`)

## Tujuan
POS Pro gratis dipakai; donasi murni sukarela. Halaman publik `/dukung` menampung
kanalnya, dan panel superadmin `/admin/donasi` merekap sekaligus memoderasinya.

**Donasi tidak membuka fitur apa pun.** Itu keputusan sadar — karena tidak ada yang bisa
dibuka, transfernya tidak perlu diverifikasi dan tidak ada yang bisa dicurangi dengan
mengaku sudah transfer.

## Kanal

| Kanal | Alur |
|---|---|
| `qris` | pindai gambar QRIS yang diunggah superadmin |
| `transfer` | transfer ke salah satu rekening yang terdaftar |
| `saweria` | tombol keluar ke Saweria, lalu dicatat di sini |

Kanal yang belum dikonfigurasi **tidak muncul** dan **ditolak validasi** — tidak ada
tombol yang mengarah ke rekening kosong, dan "sudah transfer ke QRIS" tidak bisa dicatat
kalau QRIS-nya memang tidak pernah ada. Kalau tidak ada satu pun kanal aktif, formulirnya
hilang sama sekali.

## Moderasi
Nama dan pesan donatur tampil di halaman publik, jadi **setiap donasi masuk sebagai
`pending`** dan baru terlihat setelah diterima superadmin. Tanpa itu `/dukung` jadi papan
tulis terbuka untuk siapa pun yang mau menempel spam.

| Status | Arti |
|---|---|
| `pending` | baru masuk, belum ditinjau — tidak tampil di publik, tidak dihitung terkumpul |
| `approved` | boleh tampil di dinding donatur dan ikut angka "terkumpul" |
| `rejected` | spam; barisnya tetap disimpan tapi tidak pernah tampil |

`reviewed_at` + `reviewed_by` menyimpan jejak peninjau: kalau ada pesan lolos yang
seharusnya tidak, ketahuan siapa yang menyetujuinya. Jumlah antrean `pending` dibagikan
sebagai prop Inertia `platform.pending_donations` sehingga muncul sebagai lencana di
navigasi superadmin di setiap halaman — tanpa itu spam cuma ketahuan kalau seseorang
kebetulan membuka `/admin/donasi`.

## Pengaturan (bukan `.env`)
QRIS, rekening, tautan Saweria, dan satu kalimat catatan diatur di
**`/admin/donasi/pengaturan`**, disimpan di tabel `settings` (key/value JSON).
Alasannya: ketiganya berubah tanpa alasan teknis apa pun dan tidak layak menunggu deploy.
`config/donation.php` tinggal berisi aturan nominal (preset, `DONATION_MIN`,
`DONATION_MAX`) dan batas dinding donatur.

Formulirnya di-**POST**, bukan PUT: ia mengunggah gambar dan PHP tidak mengurai
multipart pada request PUT. Gambar QRIS lama dihapus saat diganti supaya disk tidak
menumpuk berkas yatim. `App\Support\PlatformSettings` memoize isi tabel **per request**
(bukan cache lintas request) supaya superadmin tidak pernah menyimpan nomor rekening
lalu melihat halaman yang masih menampilkan nomor lama.

## Data
Tabel `donations` — **bukan entity sync**: ini tabel platform, bukan milik toko, jadi
PK auto-increment dan timestamp ISO biasa (bukan UUID + epoch ms).

Kolom penting: `user_id` (nullable, boleh anonim), `donor_name`, `donor_email`,
`amount` (**integer rupiah**, konsisten dengan uang di app ini), `message`, `channel`
(`qris|transfer|saweria`), `status` (`pending|approved|rejected`), `order_id` (unik),
`reviewed_at`, `reviewed_by`, `is_anonymous`.

`order_id` = `DON-{ymd}-{12 karakter acak}`. Bagian acaknya panjang karena `order_id`
sekaligus jadi route key halaman terima kasih — id berurut akan membuat pesan donatur
lain bisa diintip dengan menaikkan angka.

## Halaman
- **`/dukung`** — satu kolom: cara berdonasi (QRIS / rekening / Saweria) lalu formulir
  singkat (nominal, nama, email opsional, pesan, anonim). Dinding donatur di bawahnya
  hanya berisi donasi yang sudah diterima; donasi anonim tetap tampil dengan nama
  disamarkan supaya jumlah dukungan terlihat apa adanya, dan email tidak pernah ikut
  keluar. POST-nya dibatasi `throttle:10,1` karena bisa ditulis tanpa akun.
- **`/dukung/selesai/{donation}`** — halaman terima kasih. Menjelaskan bahwa nama &
  pesannya ditinjau dulu, dan tetap menampilkan tujuan pembayaran (buat yang mencatat
  duluan lalu transfer belakangan).
- **`/admin/donasi`** — antrean moderasi + rekap. Filter (cari/kanal/status/tanggal)
  jadi satu sumber kebenaran: tabel, total, chart per bulan, dan tautan ekspor CSV
  memakai query yang sama, jadi angka di layar selalu cocok dengan berkas yang terunduh.
  Tombol Terima/Tolak ada langsung di baris tabelnya.
- **`/admin/donasi/pengaturan`** — QRIS, maksimal 5 rekening, tautan Saweria, catatan.

## Kode
- `app/Models/Donation.php`, `app/Models/Setting.php`, migration `donations` + `settings`.
- `app/Support/{PlatformSettings,DonationSettings}.php`.
- `app/Actions/Donation/{RecordDonation,UpdateDonationStatus}.php`,
  `app/Actions/Admin/SaveDonationSettings.php`.
- `app/Http/Controllers/Web/DonationController.php`,
  `app/Http/Controllers/Web/Admin/{AdminDonationController,AdminDonationSettingsController}.php`.
- `resources/js/Pages/Donate/{Index,Thanks}.vue`,
  `resources/js/Pages/Admin/Donations/{Index,Settings}.vue`,
  `resources/js/Components/donate/PaymentTargets.vue`,
  `resources/js/lib/donation.ts` (label kanal/status).
- Test: `tests/Feature/Web/{DonationTest,DonationSettingsTest}.php`,
  bagian donasi di `tests/Feature/Web/AdminPanelTest.php`.
