# Arsitektur — POS Pro

Backend Laravel yang menerima & menyebarkan perubahan dari device POS Kacaw.
Prinsip: **device adalah source of truth per baris**, server = penyalur + penyimpan
yang aman-konflik (LWW) dan terisolasi per toko.

## Lapisan

```
HTTP (api/v1, route-attributes)
  └─ Controller (tipis)         app/Http/Controllers/Api/V1/*
       ├─ FormRequest           validasi 100% di sini (bukan di controller)
       └─ Action                orkestrasi   app/Actions/{Auth,Sync}/*
            ├─ SyncModel + StoreScope + SyncObserver   (tenancy otomatis)
            └─ Storage disk     (media)
```

- **Controller** hanya memanggil Action & membentuk response. Tidak ada logika
  bisnis maupun `validate()`.
- **FormRequest** — satu per endpoint ber-input (`GoogleAuthRequest`,
  `LoginRequest`, `SyncPushRequest`, `SyncPullRequest`). Aturan validasi terpusat.
- **Action** — unit orkestrasi yang bisa dites sendiri: `AuthenticateWithGoogle`,
  `LoginWithPassword`, `EnsureUserHasStore`, `PushChanges`, `ApplyChange`,
  `PullChanges`, `StoreMediaPayload`.

## Model data

- **Entity PK = UUID (char36) dari client**. Model sync: `$incrementing=false`,
  `$keyType='string'`, `$timestamps=false` (kolom waktu = epoch ms yang dikelola FE).
- **Uang = `unsignedBigInteger`** (minor units). **Timestamp = `unsignedBigInteger`**
  (epoch ms). Boolean disimpan 0/1.
- Tiap tabel entity punya: kolom domain (mirror FE) + `store_id` (FK, server-side) +
  `origin_device` + `created_at`/`updated_at`(indexed)/`deleted_at`/`sync_version`, +
  index komposit `(store_id, updated_at)`. `sales` unik `(store_id, number)`.
- `stores` = **bigint PK** (bukan UUID) karena spatie teams id = `unsignedBigInteger`.

### Atribut PHP (dimaksimalkan)
- `SyncModel` (abstract): casts sync int, relasi `store()`.
- Tiap model konkret: `#[ScopedBy([StoreScope::class])]` +
  `#[ObservedBy([SyncObserver::class])]`.
- `User`: `#[Fillable([...])]`, `#[Hidden([...])]`, trait `HasApiTokens`, `HasRoles`.
- Controller: `#[Prefix('api/v1/...')]`, `#[Get]/#[Post]`, `#[Middleware([...])]`.

## Tenancy (isolasi per toko)

`store_id` **tidak pernah** dipercaya dari payload client. Alurnya:

1. `SetCurrentStore` (middleware) resolusi toko aktif dari header `X-Store-Id`
   atau `user.current_store_id`; **abort 403** bila user bukan anggota toko itu;
   set `StoreContext` + `PermissionRegistrar::setPermissionsTeamId($store->id)`.
2. `StoreScope` (global scope) menambahkan `where store_id = StoreContext::id()`
   pada semua query model sync → read otomatis ter-scope.
3. `SyncObserver::creating()` mengisi `store_id` dari `StoreContext` saat insert →
   write otomatis ter-scope.
4. `terminate()` membersihkan `StoreContext` per request (hindari kebocoran antar
   request/test).

## Sinkronisasi

- **Push** (`PushChanges` → `ApplyChange` per envelope, transaksi terpisah):
  device-local check → allowlist → cek `id` → delete(tombstone)/upsert. **LWW**:
  apply hanya bila `payload.updated_at > stored.updated_at`, else `stale`.
  `sync_version` di-increment (audit). Kolom di-whitelist via
  `Schema::getColumnListing` (drop kolom FE-lokal `dirty`/`remote_id`).
- **Pull** (`PullChanges`): `where updated_at > since` (termasuk tombstone) urut
  ASC → payload (buang `store_id`/`origin_device`, tambah `dirty=0`/`remote_id=null`,
  media `data=null`). `cursor` = max `updated_at` (atau `since` bila kosong).
- **Reject** = exception `SyncRejection(reason: RejectReason)` ditangkap per
  envelope → masuk `rejected[]`; envelope lain tetap jalan.

## Auth

- **Google**: `AuthenticateWithGoogle` verifikasi `id_token` (via
  `GoogleTokenVerifier`) → `firstOrCreate` by `google_id`/email →
  `EnsureUserHasStore` (buat store + pivot role `owner` + set team id + `assignRole`)
  → terbitkan Sanctum token. Verifier di-bind di `AppServiceProvider`
  (`GoogleClientVerifier`) → bisa di-fake saat test.
- **Password**: `LoginWithPassword` untuk dev/test.
- Response seragam via `AuthResponse`: `{ token, user, stores }`.

## RBAC

spatie/laravel-permission, **teams = store**. `RolePermissionSeeder` mendaftar
permission & role global (team id null). Middleware `permission:...` (alias di
`bootstrap/app.php`) memproteksi route sync. `owner` = semua; `cashier` =
`sync.push`+`sync.pull`+`cashier.session`.

## Media

`StoreMediaPayload`: decode base64 → `Storage::disk(config('sync.media_disk'))->put`
→ kembalikan URL. `ApplyChange` mengisi `remote_url`, mengosongkan `data` di DB.
Batas ukuran `config('sync.media_max_bytes')` (default 8MB) → `413`/`invalid_payload`.

## Konfigurasi kunci

- `config/sync.php` — allowlist `entities` (8 nama→kelas model), `device_local`
  (`settings`), `media_max_bytes`, `media_disk`.
- `config/permission.php` — `teams => true`.
- DB dev = **SQLite** (test = in-memory). Produksi = tukar `.env` ke MySQL
  (`DB_CONNECTION=mysql`), skema identik.

## OpenAPI

Scramble memindai `api/v1` → `/docs/api` (UI) & `/docs/api.json`. FormRequest +
tipe response membuat schema akurat. `docs/api-contract.md` = kontrak naratif
(sumber kebenaran); Scramble = referensi hidup dari kode.
