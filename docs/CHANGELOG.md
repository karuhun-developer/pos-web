# Changelog

Semua perubahan penting pada **POS Pro** (backend cloud POS Kacaw) dicatat di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/) & [SemVer](https://semver.org/).

## [Unreleased]

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
