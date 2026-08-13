# POS Pro — backend & panel web aplikasi kasir POS Kacaw (Laravel)

**Aplikasi kasir (POS / point of sale) gratis dan open source untuk warung &
UMKM.** POS Pro adalah backend cloud sekaligus **panel web** untuk
[POS Kacaw](https://github.com/karuhun-developer/pos-android), aplikasi kasir
Android yang tetap jalan **tanpa internet**. Ia menaikkan app dari single-device
jadi **multi-device + backup cloud + login online** lewat sinkronisasi dua arah
berbasis outbox — lalu menambah yang butuh layar besar: kelola produk, laporan
penjualan, impor/ekspor massal, hak akses kasir, dan panel superadmin.

- **Stack:** Laravel 13 · PHP 8.4 · MySQL · Inertia 2 + Vue 3 + Tailwind v4 ·
  Sanctum 4 (bearer token) · spatie/laravel-permission 6 (RBAC per-toko/team) ·
  spatie/laravel-route-attributes · Scramble (OpenAPI) · Pest 4 · Pint.
- **Klien:** [POS Kacaw](https://github.com/karuhun-developer/pos-android) (Vue 3 +
  Capacitor + SQLite). Kontrak sinkronisasi = sumber kebenaran ada di FE
  (`src/services/sync/types.ts`), di-mirror ke `docs/api-contract.md`.

## Konsep inti

- **Sinkronisasi outbox → LWW.** Tiap perubahan di device ditulis ke outbox lalu
  dikirim sebagai `ChangeEnvelope`. Server upsert-by-`id` di-scope `store_id`
  (server-side), konflik diselesaikan **last-write-wins by `updated_at`** (epoch ms).
  `delete` = tombstone (`deleted_at` diisi, tetap dikembalikan saat pull).
- **Uang = INTEGER minor units** (rupiah bulat), **timestamp = epoch ms**,
  **PK = UUID dari client**. Tidak ada float di mana pun.
- **Tenancy multi-toko.** Satu user bisa punya banyak `store`; role per-store
  (owner/cashier) via spatie teams. Semua data ter-scope toko aktif
  (header `X-Store-Id`), isolasi antar-tenant dijaga di server.
- **`settings` TIDAK disync** (device-local: pin, tema, qris, device_id).

## Endpoint (`/api/v1`)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| `GET`  | `/health` | — | Cek server hidup. |
| `POST` | `/auth/register` | — | Daftar akun baru (email/password) → buat user + toko default (owner). Balas `201 {token,user,stores}`. |
| `POST` | `/auth/login` | — | Login email/password (dev & test). |
| `POST` | `/auth/google` | — | Login via **Google ID token** (client kirim `id_token`, server verifikasi). |
| `GET`  | `/auth/me` | Bearer | Profil user + daftar toko. |
| `POST` | `/auth/logout` | Bearer | Cabut token aktif. |
| `GET`  | `/stores` | Bearer | Daftar toko milik user. |
| `POST` | `/sync/push` | Bearer + `permission:sync.push` | Kirim batch `ChangeEnvelope[]` → `{acked[], rejected[]}` (di-key outbox id). |
| `GET`  | `/sync/pull?entity=&since=` | Bearer + `permission:sync.pull` | Tarik perubahan per-entity `updated_at > since` (incl. tombstone) → `{entity, changes[], cursor}`. |

Header wajib untuk request auth: `Authorization: Bearer <token>`,
`Accept: application/json`, `X-Device-Id: <device_id>`, `X-Store-Id: <store_id>`.

Entity yang disync (8): `categories, products, media, cashier_sessions, sales,
sale_items, cashflow_categories, cashflow_entries`.

Kontrak lengkap + skema payload per-entity: **[`docs/api-contract.md`](docs/api-contract.md)**.
OpenAPI hidup (Scramble): `/docs/api` (UI) & `/docs/api.json`.

## Panel web

| Area | Route | Isi |
|---|---|---|
| Publik | `/` , `/dukung` | landing POS Kacaw (unduh APK) & halaman donasi |
| Toko | `/dashboard` … `/pengaturan-toko` | produk, kategori, transaksi, arus kas, sesi kasir, **laporan + chart**, impor/ekspor CSV/XLSX |
| Platform | `/admin` | superadmin: semua toko, pengguna, donasi, log sync |

Semua tulisan dari web lewat `WriteEntity` (pembungkus jalur sync yang sama
dengan `POST /sync/push`), jadi perubahan dari browser **ikut ter-pull perangkat
Android** dan penghapusan jadi tombstone, bukan hard delete.
Detailnya: [`docs/features/web-ui.md`](docs/features/web-ui.md).

## Setup

```bash
git clone git@github.com:karuhun-developer/pos-web.git pos-pro
cd pos-pro
composer install
cp .env.example .env
php artisan key:generate
# atur koneksi MySQL di .env (DB_DATABASE=pos_pro, dst)
php artisan migrate --seed        # bikin skema + role/permission + akun demo
npm install && npm run build      # aset UI web (Inertia + Vue)
php artisan serve                 # http://localhost:8000
```

Akun superadmin: isi `SUPERADMIN_EMAIL`/`SUPERADMIN_PASSWORD` di `.env` sebelum
`--seed` (tanpa password, seeder-nya sengaja dilewati), atau naikkan user yang sudah ada
dengan `php artisan pos:superadmin kamu@email.com`.

### Konfigurasi `.env` penting

| Var | Fungsi |
|---|---|
| `GOOGLE_CLIENT_ID` | Client ID Google (tipe **Web application**) buat verifikasi ID token dari app Android. |
| `GOOGLE_CLIENT_SECRET` | Hanya untuk **login Google di web** (OAuth redirect via Socialite). Kosong = tombol Google di halaman masuk disembunyikan; jalur Android tetap jalan tanpa ini. |
| `GOOGLE_REDIRECT_URI` | Default `${APP_URL}/auth/google/callback` — daftarkan persis ini sebagai *Authorized redirect URI* di Google Cloud Console. |

### Akun demo (dari seeder)

- Owner: `owner@example.com` / `password` (langsung punya toko pertama)

## Testing

```bash
php artisan test        # Pest: feature + unit
vendor/bin/pint --dirty # code style
```

Cakupan: auth (register, login, Google mock), sync push (upsert, LWW menang/kalah,
delete→tombstone, unknown_entity, isolasi tenant), sync pull (cursor, tombstone,
scope store), RBAC (kasir ditolak `catalog.manage`).

## Dokumentasi

`docs/` — `PRD.md`, `architecture.md`, `api-contract.md`, `CHANGELOG.md`, dan
`features/` (web-ui, donations, import-export, authentication-google, rbac-stores,
sync-endpoints, tenancy-multistore, media-storage).
