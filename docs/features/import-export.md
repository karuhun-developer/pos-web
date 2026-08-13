# Fitur — Impor & Ekspor

**Status:** ✅ Selesai (branch `feat/web-ui`)

## Tujuan
Halaman `/impor-ekspor` memberi pemilik toko yang tidak ada di Android: unduh data dalam
CSV/XLSX, dan unggah data massal dengan **pratinjau dua langkah** sebelum apa pun
tertulis.

## Dataset
Enum `App\Support\ImportExport\Dataset` — bukan array config, karena nilainya dipakai
langsung sebagai segmen URL sehingga dataset tak dikenal gugur di route-binding, bukan
jadi cabang `if` di controller.

| Dataset | URL | Ekspor | Impor | Pakai rentang tanggal |
|---|---|---|---|---|
| Produk | `produk` | ✅ | ✅ | — |
| Kategori | `kategori` | ✅ | ✅ | — |
| Transaksi | `transaksi` | ✅ (satu baris per item struk) | — | ✅ |
| Arus kas | `kas` | ✅ | ✅ | ✅ |
| Sesi kasir | `sesi-kasir` | ✅ | — | ✅ |
| Stok opname | `stok` | ✅ | ✅ | — |
| Ringkasan laporan | `ringkasan` | ✅ | — | ✅ |

Setiap dataset yang bisa diimpor menyediakan **template kosong** (header + satu baris
contoh) dari halaman yang sama.

## Izin: unduh ≠ unggah
Dua izin berbeda dipakai dengan sengaja:

- **Ekspor** cukup `reports.view`.
- **Impor** mengikuti izin menulis data aslinya (`Dataset::importPermission()`):
  `catalog.manage` untuk produk/kategori/stok, `cashflow.manage` untuk kas.

Menulis ratusan baris sekaligus jelas lebih berbahaya daripada membacanya. Kasir tidak
punya keduanya → `403` di kedua arah.

## Impor dua langkah

```
POST io.preview  →  berkas disimpan + dianalisis, TIDAK ada yang ditulis
                    hasil dikirim lewat flash session (POST-redirect-GET)
POST io.commit   →  berkas dibaca ULANG, baris sah ditulis lewat WriteEntity
```

- Berkas sementara disimpan di disk `local` (`imports/{uuid}.{ext}`) dan **dikunci ke
  sesi pengunggahnya**. Menyimpan ribuan baris hasil parse di session akan meledak untuk
  berkas besar, dan meminta user mengunggah ulang saat konfirmasi membuka celah
  berkasnya berganti di antara dua langkah. Token dari sesi lain ditolak.
- Pratinjau yang tidak pernah dikonfirmasi dibersihkan pada unggahan berikutnya (lebih
  dari 24 jam) — bukan lewat scheduler, supaya tidak ada job yang harus dijaga.
- Layar pratinjau menandai tiap baris **baru / diperbarui / error** beserta alasannya;
  maksimal 200 baris dikirim ke browser, sisanya diwakili ringkasan.
- **Baris error dilewati, bukan membatalkan seluruh berkas**: 3 baris rusak dari 500
  tidak boleh menyandera 497 baris yang benar. Jumlah yang dilewati dilaporkan di flash
  message.

### Hasil impor ikut ter-sync
`CommitImport` memanggil `Importer::apply()` yang menulis lewat **`WriteEntity`**, jadi
baris hasil impor punya `updated_at` epoch ms dan langsung ikut `GET /api/v1/sync/pull`
seperti perubahan lain. Diuji di `tests/Feature/Web/ImportExportTest.php`.

### Aturan pencocokan
- **Produk** — SKU dulu, baru barcode. SKU adalah kunci yang dikelola toko sendiri,
  sedangkan barcode bisa dipakai bersama beberapa varian dari pabrik. Baris tanpa
  keduanya ditolak; penanda yang muncul dua kali di satu berkas ditolak.
- Kolom yang **dikosongkan** pada baris update tidak ikut dikirim: `ApplyChange` hanya
  menulis kolom yang ada di payload, jadi gambar dan kolom lain yang tidak ada di berkas
  tetap utuh.
- **Kategori yang tidak dikenal menolak barisnya**, tidak diam-diam membuat kategori
  baru — salah ketik satu huruf akan melahirkan kategori kembar yang baru ketahuan
  berminggu-minggu kemudian.

## Ekspor
Streaming lewat **openspout** (CSV & XLSX), jadi ekspor besar tidak menahan seluruh
tabel di memori. Rentang tanggal diambil dari `ReportPeriod` yang sama dengan halaman
laporan, sehingga berkas yang diunduh persis mencerminkan angka di layar.

## Catatan implementasi
- `ExportRequest::fileFormat()` **tidak boleh** dinamai `format()`:
  `Illuminate\Http\Request` sudah punya `format($default = 'html')` untuk negosiasi
  konten, dan menimpanya dengan tanda tangan berbeda adalah fatal error PHP — yang baru
  meledak saat kelasnya di-refleksi (mis. Ziggy memindai route), jadi **seluruh halaman**
  ikut mati, bukan cuma ekspor.
- Aturan `mimes:csv,txt,xlsx` menyertakan `txt` karena CSV yang ditebak PHP sebagai
  `text/plain` akan gagal `mimes:csv`; berkasnya sendiri tetap dibaca sebagai CSV oleh
  openspout.

## Kode
- `app/Support/ImportExport/Dataset.php`, `app/Support/Spreadsheet.php`, `ReportPeriod.php`.
- `app/Actions/ImportExport/{PreviewImport,CommitImport,AnalyseImport,ExportDataset,BuildTemplate}.php`
  + `Export/*`, `Import/*`, `Contracts/{Exporter,Importer}.php`.
- `app/Http/Controllers/Web/ImportExportController.php`,
  `app/Http/Requests/Web/{ExportRequest,ImportPreviewRequest,ImportCommitRequest}.php`.
- `resources/js/Pages/ImportExport/Index.vue`.
- Test: `tests/Feature/Web/ImportExportTest.php` (8 kasus).
