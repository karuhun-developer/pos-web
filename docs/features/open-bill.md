# Fitur — Open Bill

**Status:** kontrak siklus hidup dan visibilitas POS Pro

## Tujuan

Open Bill adalah transaksi yang ditahan di perangkat kasir asal. POS Pro hanya
menyinkronkan dan menampilkannya agar pemilik dapat melihat konteksnya; panel web
tidak boleh menjadi jalur untuk melanjutkan pembayaran, membatalkan, atau membuangnya.

## Siklus hidup

```text
open ── selesai dari perangkat asal ──> completed ── void ──> void
  │
  └── dibuang dari perangkat asal ──> tombstoned (deleted_at terisi)
```

| State | Makna | Efek bisnis |
|---|---|---|
| `open` | Keranjang ditahan, belum dibayar. | Tidak membuat reservasi, perubahan stok, perubahan sesi kasir, jurnal akuntansi, laporan, atau arus kas. |
| `completed` | Kasir asal menyelesaikan pembayaran. | Baru pada tahap ini stok, sesi, akuntansi/laporan, dan arus kas dapat menerima dampak transaksi. Validasi stok dilakukan ulang terhadap stok terbaru sebelum penyelesaian. |
| tombstoned | Kasir asal membuang Open Bill melalui operasi sync `delete`. | Perangkat lain menghapus salinan lokalnya saat menerima `deleted_at`; tidak ada efek bisnis yang perlu dibalik karena Open Bill belum pernah memengaruhi bisnis. |
| `void` | Transaksi yang sudah `completed` kemudian dibatalkan. | Mengikuti alur void transaksi selesai yang sudah ada. |

Hanya perangkat yang identitasnya cocok dengan `origin_device_id` boleh menyelesaikan
atau membuang Open Bill. Pembatasan ini mencegah dua perangkat mencoba menyelesaikan
keranjang yang sama dan menjadikan validasi stok segar di langkah completion bermakna.

## Data tersinkron

Entity `sales` untuk Open Bill membawa data tambahan berikut. Semua waktu adalah epoch
milliseconds dan semua nilai uang tetap integer rupiah.

| Field | Ketentuan |
|---|---|
| `status` | `open`, `completed`, atau `void`. |
| `sold_at` | Nullable. `null` selama `open`, terisi saat `completed`. |
| `opened_at` | Waktu keranjang ditahan; dipakai untuk urutan dan tampilan Open Bill. |
| `open_bill_label` | Label kasir opsional untuk menemukan Open Bill. |
| `origin_device_id` | ID perangkat kasir pembuat; dasar pembatasan sumber. |
| `sale_items` | Menyimpan `name_snapshot` dan `price_snapshot` bersama qty, diskon, dan subtotal saat ditahan. Snapshot dipakai untuk riwayat yang stabil, bukan untuk melewati validasi stok atau harga di completion. |

Perubahan ini **additive** di API v1: endpoint push/pull, envelope, dan entity `sales`
tidak berubah. Klien lama tetap dapat mengirim transaksi `completed`/`void` lama; klien
yang mendukung Open Bill mengirim field tambahan pada full-row payload dan harus mampu
menerima field tambahan tersebut dari pull.

## POS Pro: tampilan saja

Halaman Transaksi POS Pro menampilkan Open Bill dengan badge peringatan `Open Bill`,
label, dan waktu `opened_at`; pencarian mencakup nomor struk maupun label. Detailnya
menampilkan label, waktu ditahan, dan delapan karakter pertama ID perangkat asal.
Metode bayar, nilai dibayar, kembalian, dan seluruh kontrol kasir disembunyikan karena
belum ada pembayaran dan POS Pro bukan perangkat asal.

Copy yang wajib ditampilkan pada detail Open Bill:

> Penyelesaian atau pembatalan Open Bill dilakukan dari perangkat kasir asal.

Lihat juga [api-contract.md](../api-contract.md),
[sync-endpoints.md](sync-endpoints.md), dan [web-ui.md](web-ui.md).
