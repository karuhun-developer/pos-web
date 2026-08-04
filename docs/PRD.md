# POS Pro — Product Requirements

**Status:** ✅ Phase 6 (v0.1.0) · Backend cloud untuk **POS Kacaw** (app kasir offline-first).

## Masalah
POS Kacaw jalan 100% offline di satu device. Pedagang butuh: **backup cloud**,
**multi-device** (kasir + pemilik lihat data yang sama), dan **login online**.
Semua data bisnis sudah tersimpan sync-ready di device (pola outbox) — yang kurang
hanya server yang menerima & menyebarkannya.

## Tujuan
- Terima perubahan dari device (**push**) dan sebarkan ke device lain (**pull**),
  aman terhadap konflik & tanpa kehilangan data.
- **Multi-toko**: satu akun bisa punya banyak toko; data terisolasi per toko.
- **Login**: Google (verifikasi ID token) untuk end-user + email/password untuk dev/test.
- **RBAC**: pemilik vs kasir, izin berbeda, ber-scope per toko.
- **Dokumentasi API** yang jadi kontrak tunggal FE↔BE (+ referensi OpenAPI hidup).

## Persona
- **Pemilik (owner)** — punya toko, kelola katalog, lihat laporan, semua akses.
- **Kasir (cashier)** — transaksi + sync + buka/tutup kasir; tidak kelola katalog.

## Scope Phase 6
- Auth: `POST /auth/google`, `POST /auth/login`, `GET /auth/me`, `POST /auth/logout`.
- Toko: `GET /stores`; multi-toko dengan role per toko.
- Sync: `POST /sync/push`, `GET /sync/pull` untuk 8 entity (lihat api-contract.md).
- Media: byte gambar disimpan ke storage, dilayani via `remote_url`.
- RBAC: spatie/laravel-permission (teams = toko).
- OpenAPI via Scramble (`/docs/api`).

## Non-goals (Phase 6)
- UI web admin (murni API).
- Realtime/websocket (sync = pull berbasis cursor + interval FE).
- Pembayaran/billing SaaS, laporan lanjutan, impor massal.
- OAuth redirect flow Google (kita pakai verifikasi ID token dari client).

## Kriteria sukses
- `composer test` hijau (feature + unit), termasuk isolasi tenant & LWW.
- Bentuk JSON push/pull **byte-compatible** dengan tipe FE
  (`PushResult`/`PullResult` di `pos-kacaw/src/services/sync/types.ts`).
- Scramble menampilkan seluruh endpoint `api/v1`.
