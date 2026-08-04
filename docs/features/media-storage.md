# Fitur — Penyimpanan Media (gambar produk)

**Status:** ✅ Selesai (Phase 6)

## Tujuan
Byte gambar produk (dikirim FE sebagai base64) disimpan ke storage server dan
dilayani via URL, bukan diulang-ulang sebagai base64 di setiap pull. Menyediakan
seam ke object storage tanpa mengubah kode entity lain.

## Data & Aturan
- `media` disync seperti entity lain, tapi byte diperlakukan khusus.
- **Push** (`insert`/`update`): payload boleh menyertakan `data` (base64 tanpa
  prefix `data:`). Server menyimpan byte ke disk (`config('sync.media_disk')`,
  default `public`), mengisi `remote_url`, lalu **mengosongkan `data`** di DB.
  `hash` (SHA-256) dipakai dedup di sisi FE.
- **Pull**: row `media` dikembalikan dengan `remote_url` terisi & `data = null`.
  FE memuat dari `remote_url` bila ada, jika tidak dari `data`.
- **Limit**: `config('sync.media_max_bytes')` (default 8MB). Lewat batas → `413`
  (atau `rejected: invalid_payload`).
- Swap ke object storage = cukup ganti disk di config; kolom & kontrak tetap.

## Kode
- `app/Actions/Sync/StoreMediaPayload.php` — decode base64 → `Storage::disk(...)->put`
  → kembalikan URL.
- `app/Actions/Sync/ApplyChange.php` — memanggil `handleMedia` (isi `remote_url`,
  null-kan `data`) saat entity = `media`.
- `app/Models/Media.php` — casts kolom int (`width/height/bytes`).
- `config/sync.php` — `media_disk`, `media_max_bytes`.
- Test: `tests/Feature/SyncMediaTest.php` (base64 disimpan, `data` null di pull,
  `remote_url` terisi).
