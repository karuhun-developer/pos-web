# Changelog

Semua perubahan penting pada **POS Pro** (backend cloud POS Kacaw) dicatat di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/) & [SemVer](https://semver.org/).

## [Unreleased]

### Added — UI Web (Fase 7, branch `feat/web-ui`)
- **Stack web**: Inertia 2 + Vue 3 + Tailwind v4 + Vite + TypeScript, Ziggy untuk
  `route()` di sisi Vue, ECharts (`vue-echarts`) untuk chart. Route halaman ada di
  `routes/web/{guest,app,admin}.php` — **bukan** lewat route-attributes, yang memasang
  route di luar grup `web` (tanpa session/CSRF/Inertia).
- **`app/Actions/Sync/WriteEntity.php`** — satu-satunya pintu tulis server, membungkus
  `ApplyChange` yang sama dengan `POST /sync/push`. Semua CRUD web & hasil impor lewat
  sini, jadi `updated_at` epoch ms bergerak dan perubahannya **ikut ter-pull Android**;
  hapus menghasilkan **tombstone**, bukan hard delete. Uji regresinya
  `tests/Feature/Web/WebSyncTest.php`.
- **CRUD web area toko**: produk (termasuk unggah gambar lewat entity `media`),
  kategori, transaksi + void, arus kas + kategori kas, sesi kasir, pengaturan toko,
  ganti toko aktif.
- **Laporan & chart** (`app/Actions/Report/*`, `resources/js/Components/reports/*`):
  KPI + delta, tren penjualan 2 seri, heatmap jam ramai, produk terlaris, komposisi
  metode bayar, HPP vs margin, arus kas diverging, selisih laci, inventori. Satu filter
  periode men-scope seluruh halaman; setiap chart punya tampilan tabel; bucketing
  tanggal di PHP (Asia/Jakarta), bukan SQL khusus driver.
- **Cetak/PDF laporan** (`/laporan/cetak`): Blade print-friendly
  (`resources/views/reports/print.blade.php`), chart diganti tabel karena di kertas
  tidak ada tooltip, dan "simpan sebagai PDF" diserahkan ke dialog cetak browser —
  tanpa pustaka PDF baru beserta font & layout engine-nya. Rentang waktu dibawa lewat
  querystring supaya kertas dan layar menampilkan angka yang sama.
- **Impor/ekspor** (`/impor-ekspor`): ekspor streaming openspout CSV **dan** XLSX untuk
  7 dataset, template kosong, dan **impor dua langkah** (pratinjau menandai
  baru/diperbarui/error → terapkan). Baris error dilewati, bukan membatalkan berkas.
  Ekspor butuh `reports.view`; impor butuh izin tulis data aslinya.
  Lihat `docs/features/import-export.md`.
- **Login web**: email/password (rate limit 5×/menit per email+IP) + **OAuth redirect
  Google** lewat Socialite. `UpsertGoogleUser` dipakai bersama jalur ID-token Android,
  jadi satu email tetap satu user. Lihat `docs/features/authentication-google.md`.
- **Superadmin & panel platform `/admin`**: ringkasan lintas toko, daftar toko +
  drill-down, daftar pengguna + promosi/penurunan superadmin, daftar donasi, dan
  **log sync** (`/admin/sync`) — perangkat mana yang terakhir menulis, berapa row
  yang ia tulis, serta jumlah row & pergerakan terakhir per entity. Tidak ada tabel
  audit baru: `origin_device` + `updated_at` sudah menempel di tiap row entity, jadi
  `SyncActivity` membacanya langsung dan log tidak bisa bercerita beda dari datanya.
  Role `superadmin` disimpan dengan **sentinel `team_id = 0`** (`SetSuperadmin`) karena
  `model_has_roles.team_id` NOT NULL dan bagian dari PK komposit; `User::isSuperadmin()`
  membaca pivot langsung tanpa filter team. Command `php artisan pos:superadmin {email}`.
- **Policy kepemilikan data** (`app/Policies/*` + `ChecksStoreOwnership`): produk milik
  toko A tidak bisa diubah/dihapus anggota toko B → **403**; kasir tidak bisa menghapus
  produk; superadmin lolos lewat `Gate::before`. Catatan: pada route web,
  `SubstituteBindings` berjalan sebelum middleware `store`, jadi yang menghentikan akses
  lintas toko adalah **policy (403)**, bukan `StoreScope` (yang fail-open).
- **Donasi**: halaman publik `/dukung` (manual tanpa verifikasi, Paywuz, tautan
  eksternal), dinding donatur, panel `/admin/donasi` (filter, total, chart bulanan,
  tandai lunas, ekspor CSV), dan webhook `POST /api/v1/webhooks/paywuz` bertanda tangan
  HMAC (`hash_equals`; salah → 401) yang **idempoten**.
  Lihat `docs/features/donations.md`.
- **Docs**: `docs/features/{web-ui,donations,import-export}.md`;
  `authentication-google.md` & `rbac-stores.md` diperbarui; `api-contract.md` §7
  webhook; non-goal "UI web admin"/"impor massal" dicabut dari PRD.
- **Test**: `tests/Feature/Web/` — 53 kasus (kepemilikan, sync dari web, panel admin,
  auth web + Google, impor/ekspor, donasi + webhook, laporan cetak + gating
  `reports.view`, smoke semua halaman).

### Fixed
- **Props Inertia dievaluasi sebelum konteks toko ada** — `Inertia\Middleware::handle()`
  memanggil `share()` **sebelum** `$next($request)`, jadi blok `auth` yang dihitung
  langsung berjalan sebelum middleware `store` menyetel `StoreContext`. Akibatnya
  `auth.current_store` null dan `auth.user.permissions` kosong: topbar menampilkan
  "Pilih toko" dan **seluruh tombol tulis hilang**, bahkan untuk pemilik toko. Blok
  `auth` sekarang berupa closure (baru diresolve saat halaman dirender). Bug ini lolos
  dari 79 test, pint, `vue-tsc`, dan build — yang menangkapnya cuma melihat halamannya
  di browser. Regresinya dikunci di `tests/Feature/Web/PageSmokeTest.php`.
- **`inertia.pages.ensure_pages_exist`** dinyalakan di luar produksi + `PageSmokeTest`
  yang menyusuri semua route GET web: halaman yang komponen Vue-nya belum dibuat
  (kasusnya `Pages/Dashboard.vue`, yang memang belum ada) dulu hanya ketahuan sebagai
  "Page not found" di browser; sekarang render-nya gagal keras di test.
- **`DisplayTime::label()` tidak pernah ada** — dipanggil dua kali di
  `DashboardController` (fatal begitu `/dashboard` dibuka); diganti
  `DisplayTime::toLocal()->format()`.
- **Label persentase pada segmen metode bayar tidak terbaca** di isian terang
  (`#eda100`). Tinta label kini dihitung dari luminansi isian lewat
  `charts/theme.ts` → `inkOn()`, bukan dipukul rata putih. Panel yang sama dapat
  rincian rupiah per metode di bawah batang — satu batang bertumpuk cuma menjawab
  proporsi, dan sisa tinggi kartu jadi terpakai.
- **Halaman laporan hanya butuh keanggotaan toko** — `/laporan` memakai
  `SalePolicy::viewAny()`, yang cuma memeriksa user anggota toko aktif. Akibatnya
  **kasir bisa membuka omzet, margin, dan selisih laci seluruh toko** hanya dengan
  mengetik URL-nya, padahal izin `reports.view` memang tidak diberikan kepadanya.
  Sekarang ada `SalePolicy::viewReports()` (keanggotaan **dan** `reports.view`) yang
  dipanggil di `ReportController::index()` maupun `print()`; item menu "Laporan" pun
  disaring dari sidebar untuk yang tidak berizin — penegakannya tetap di policy, jadi
  URL langsung ikut ditolak. Dijaga `tests/Feature/Web/ReportPrintTest.php`.
- **Kolom "Keluar" pada laporan cetak menampilkan negatif ganda** — server mengirim
  pengeluaran bertanda minus untuk chart diverging; di kolom yang judulnya sudah
  "Keluar", tandanya dicetak nilai mutlak.
- **`ExportRequest::format()` → `fileFormat()`** — menimpa
  `Illuminate\Http\Request::format($default = 'html')` dengan tanda tangan berbeda
  adalah fatal error PHP yang baru meledak saat kelasnya di-refleksi (Ziggy memindai
  route), sehingga **seluruh halaman Inertia** ikut mati, bukan cuma ekspor.
- **`config/inertia.php`** ditambahkan: `pages.paths` diarahkan ke `resources/js/Pages`
  (bawaan paket menunjuk `js/pages` huruf kecil → selalu gagal di Linux).

### Added
- **Kolom `products.barcode_type`** (`string(20)`, default **`EAN13`**) + index
  `(store_id, barcode)` — simbologi barcode yang dipakai klien buat merender &
  memvalidasi lewat JsBarcode. **Tidak ada perubahan kode sync**: `ApplyChange` dan
  `PullChanges` schema-driven, jadi kolomnya langsung ikut push & pull. Klien lama
  yang belum mengirim kolom ini jatuh ke default DB.
  Index sengaja **bukan unique** — `PushChanges` cuma menangkap `SyncRejection`,
  jadi pelanggaran unique bakal melempar `QueryException` mentah dan menggagalkan
  seluruh batch; keunikan barcode ditegakkan di klien.
  Catatan operasional: `ApplyChange::$columnCache` static per-proses → **restart
  worker/Octane setelah `migrate`**.
  Test: `SyncPushTest` (dengan & tanpa `barcode_type`), `SyncPullTest`.
- **`POST /auth/register`** — registrasi akun baru email/password: buat user +
  toko default (`owner`) via `EnsureUserHasStore`, terbitkan Sanctum token, balas
  `201 { token, user, stores }`. Validasi email unik & password min 6. Test:
  `tests/Feature/AuthRegisterTest.php`.
- **Manajemen outlet in-app**: `POST /stores` (buat outlet baru, pembuat jadi
  `owner`, balas `201 { store, stores }`) & `PATCH /stores/{id}` (ganti nama,
  khusus owner → `403` selain owner). Action `CreateStore`. Test:
  `tests/Feature/StoreManagementTest.php` (5 kasus).

## [0.1.0] — 2026-08-04 · Phase 6

Rilis pertama backend sinkronisasi. Menaikkan POS Kacaw dari single-device jadi
multi-device + backup cloud + login online.

### Added
- **Scaffold Laravel 13** (PHP 8.4) + Sanctum 4, spatie/laravel-permission 8
  (teams=store), spatie/laravel-route-attributes, dedoc/scramble, google/apiclient.
- **API v1** (`api/v1`, routing via atribut) — 8 endpoint:
  `POST /auth/google`, `POST /auth/login`, `GET /auth/me`, `POST /auth/logout`,
  `GET /stores`, `POST /sync/push`, `GET /sync/pull`, `GET /health`.
- **Autentikasi**: Google ID-token (verifikasi via `Google\Client`, di-abstraksi
  `GoogleTokenVerifier` agar bisa di-mock) + email/password untuk dev/test. Token =
  Sanctum bearer.
- **Sinkronisasi 2 arah** untuk 8 entity (`categories, products, media,
  cashier_sessions, sales, sale_items, cashflow_categories, cashflow_entries`):
  push (upsert-by-id, LWW by `updated_at`, delete→tombstone) & pull (cursor
  berbasis `updated_at`, tombstone ikut). `settings` dikecualikan (device-local).
- **Multi-tenant**: `store_id` diisi server-side via `StoreContext` + `StoreScope`
  (global scope) + `SyncObserver`; middleware `SetCurrentStore` resolusi toko aktif
  dari `X-Store-Id`/`current_store_id`, `403` bila bukan anggota.
- **RBAC** (spatie teams): role `owner`/`cashier`; permission `sync.push`,
  `sync.pull`, `catalog.manage`, `reports.view`, `cashier.session`.
- **Media storage**: base64 push → disimpan ke disk (`config/sync.php`
  `media_disk`), `remote_url` diisi, `data` dikosongkan; pull balikin `remote_url`.
- **Pattern**: Action class (`app/Actions/{Auth,Sync}`) untuk orkestrasi; SEMUA
  validasi via FormRequest (tidak ada `$request->validate()` di controller);
  atribut PHP dimaksimalkan (`#[ScopedBy]`, `#[ObservedBy]`, `#[Prefix]`,
  `#[Get]/#[Post]`, `#[Middleware]`, `#[Fillable]/#[Hidden]`).
- **OpenAPI** via Scramble di `/docs/api` + `/docs/api.json`.
- **Test** (Pest 4): feature (auth, push, pull, RBAC, media, tenant isolation) +
  unit (`ApplyChange` LWW/allowlist). `composer test` hijau.
- **Docs**: PRD, architecture, api-contract, features/*.

[Unreleased]: https://github.com/karuhun-developer/pos-web/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/karuhun-developer/pos-web/releases/tag/v0.1.0
