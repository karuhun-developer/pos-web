# Fitur — Autentikasi (Google ID token + Password)

**Status:** ✅ Selesai (Phase 6)

## Tujuan
Login online untuk POS Kacaw tanpa alur OAuth redirect. Client mendapatkan **Google
ID token** dari Google Sign-In lalu mengirimnya ke server; server **memverifikasi**
token itu ke Google dan menerbitkan **Sanctum bearer token**. Disediakan juga login
email/password khusus dev & test. Setiap user baru otomatis punya satu toko + role
`owner`.

## Data & Aturan
- Endpoint: `POST /auth/google {id_token}`, `POST /auth/login {email,password}`,
  `GET /auth/me`, `POST /auth/logout`.
- Verifikasi `id_token` via `Google\Client::verifyIdToken()` (client id dari
  `config/services.php` → `GOOGLE_CLIENT_ID`). Gagal → `401`.
- User dicocokkan `firstOrCreate` by `google_id`, fallback `email`. Kolom user
  tambahan: `google_id` (unik), `avatar_url`, `current_store_id`. `password`
  nullable (user Google tak punya password).
- User tanpa toko → dibuatkan store default, di-attach ke pivot `store_user` dengan
  `role='owner'`, di-`assignRole('owner')` (team = store), `current_store_id` diset.
- Response seragam: `{ token, user, stores[] }` (`stores[].role` dari pivot).
- `logout` mencabut personal access token yang dipakai request (→ `204`).
- Validasi 100% di FormRequest: `GoogleAuthRequest` (`id_token` required string),
  `LoginRequest` (`email`, `password` required). Tidak ada `validate()` di controller.

## Kode
- `app/Http/Controllers/Api/V1/GoogleAuthController.php`, `LoginController.php`,
  `AuthController.php` (me/logout) — semua route via atribut.
- `app/Actions/Auth/AuthenticateWithGoogle.php`, `LoginWithPassword.php`,
  `EnsureUserHasStore.php`.
- `app/Contracts/GoogleTokenVerifier.php` (interface) +
  `app/Services/Google/GoogleClientVerifier.php` (impl) — di-bind di
  `AppServiceProvider`; di test diganti `tests/Support/FakeGoogleVerifier` (token
  `"valid:<sub>:<email>"`).
- `app/Support/AuthResponse.php` — pembentuk payload `{token,user,stores}`.
- `app/Http/Requests/GoogleAuthRequest.php`, `LoginRequest.php`.
- Test: `tests/Feature/AuthGoogleTest.php`, `AuthLoginTest.php`.
