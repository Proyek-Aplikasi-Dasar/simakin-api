# Rencana Sprint 0 (Revisi) — Autentikasi

**Status:** Rencana disetujui, siap eksekusi
**Periode:** Sprint 0 (revisi)
**Prasyarat:** `develop` sebagai base; branch kerja `feature/*` / `docs/*`

> **Revisi 2026-10-20:** Identitas login berpindah dari **email** ke **`username`** (siswa = NIS, guru = NIP, admin = kustom; kolom `email` menjadi nullable & tidak dipakai untuk login). Poin yang menyinggung "email unique" pada dokumen ini dimaknai sebagai **username unique**.

---

## 1. Latar Belakang & Keputusan

Sprint 0 sebelumnya mengimplementasikan reset password berbasis **email token** (`POST /api/forgot-password`, `POST /api/reset-password`). Pekerjaan itu dibatalkan dan diganti dengan pendekatan berikut.

| Topik | Keputusan |
|---|---|
| Ubah kata sandi | Dilakukan langsung di halaman pengaturan akun (web & mobile) dengan form: Kata Sandi Saat Ini, Kata Sandi Baru, Konfirmasi Kata Sandi Baru |
| Mekanisme email | **Dihapus** dari PAD 1. Notifikasi hanya via **database** (`notifications` table) |
| Lupa kata sandi | Pengguna **menghubungi admin**; admin reset kata sandi (endpoint `PUT /api/users/{id}/password`, dijadwalkan Sprint 2) |
| Setelah ubah sandi | Cabut **semua token/sesi lain** (kecuali sesi saat ini) |
| Session driver | `SESSION_DRIVER=database` agar pencabutan sesi web dapat dilakukan server-side (tabel `sessions` sudah ada) |
| Nama endpoint | `PUT /api/user/password` (sejajar dengan `GET /api/user`) |

**Alasan:** Form ubah kata sandi dengan verifikasi kata sandi saat ini tidak membutuhkan email, lebih aman (out-of-band session), dan sesuai desain frontend web/mobile yang ada.

**Catatan kepatuhan P2-21:** Butir "prosedur forgot/reset password" dipenuhi melalui jalur pemulihan **admin reset kata sandi** (Sprint 2), bukan email.

---

## 2. Ruang Lingkup

### Termasuk
- Endpoint register pengguna baru (admin only) — guru/admin
- Endpoint ubah kata sandi sendiri (semua role, web & mobile)
- Pencabutan token mobile & sesi web lain saat ubah sandi
- Perpindahan session driver ke `database`
- Test otomatis (login, role guard, register, ubah sandi)
- Koreksi dokumentasi (hapus alur email)

### Tidak Termasuk (ditunda)
- Admin reset kata sandi user (`PUT /api/users/{id}/password`) → Sprint 2
- Auto-create profil guru saat register → Sprint 2 (tabel `guru` belum ada)
- Notifikasi email di PAD 1 → dihapus

---

## 3. Strategi Git

- Branch kerja dibuat dari `develop`, di-merge kembali ke `develop` secara **merge lokal** (`--no-ff`). Tidak push/PR.
- Format commit: `[BE] feat: ...` / `[BE] fix: ...` / `[BE] docs: ...`
- 1 fitur = 1 branch. Urutan eksekusi:

| # | Branch | Isi | Merge ke |
|---|---|---|---|
| B1 | `test/auth-fondasi` | Test login & role guard existing | develop |
| B2 | `feature/auth-register` | Endpoint register (admin-only) | develop |
| B3 | `feature/auth-change-password` | Endpoint ubah kata sandi + cabut sesi | develop |
| B4 | `docs/auth-adjustment` | Koreksi dokumentasi (paling akhir) | develop |

---

## 4. Tahap Pengerjaan

### Tahap 0 — Pembersihan Pekerjaan Sprint 0 Lama
Pekerjaan Sprint 0 sebelumnya belum ter-commit (ada di working tree `develop`). Dihapus dengan urutan:
1. `php artisan migrate:rollback --step=1` (drop `password_reset_tokens`)
2. `git restore` file yang dimodifikasi: `app/Models/User.php`, `config/app.php`, `routes/api.php`, `docs/API-Docs-fondasi.md`, `.agents/tech-specs/2026/003-api-endpoints.md`, `.agents/tasks/backend-roadmap.md`
3. Hapus file/direktori baru: `PasswordResetController`, `RegisterController`, `app/Http/Requests/`, `app/Http/Resources/`, `app/Notifications/`, migration `password_reset_tokens`, `tests/Feature/Auth/`
4. Verifikasi: `git status` bersih, `php artisan test` kembali baseline

### Tahap B1 — `test/auth-fondasi`
- `tests/Feature/Auth/LoginTest.php`: login mobile sukses + token; kredensial salah; field kosong; siswa dilarang login web; `GET /api/user`; akses tanpa login 401; logout menghapus token
- `tests/Feature/Auth/RoleGuardTest.php`: admin/guru/siswa/tamu terhadap `role:admin` dan `role:admin,guru`
- Commit: `[BE] feat: test fondasi autentikasi & role guard`

### Tahap B2 — `feature/auth-register`
- `RegisterRequest` (username unik; `role_id` hanya admin/guru; password min 8 + confirmed)
- `RegisterController@register` → `POST /api/register`, response `{ message, data }` 201
- `UserResource`
- Route dalam `auth:sanctum` + `role:admin`
- `RegisterTest` (sukses admin/guru; tolak role siswa; username duplikat; konfirmasi beda; 403 guru/siswa; 401 tamu)
- Update `003-api-endpoints.md` (1.4 Done) & `docs/API-Docs-fondasi.md` (bagian Register)
- Commit: `[BE] feat: endpoint registrasi pengguna oleh admin`

### Tahap B3 — `feature/auth-change-password`
- `UpdatePasswordRequest` (`current_password` required; `password` confirmed + min 8)
- `PasswordController@update` → `PUT /api/user/password`:
  - verifikasi manual `Hash::check` terhadap `$request->user()->password` (kompatibel guard web & sanctum)
  - update password (cast `hashed`)
  - cabut token lain (`personal_access_tokens` milik user selain token saat ini)
  - cabut sesi web lain (hapus baris `sessions` milik user selain sesi saat ini) + `session()->regenerate()`
- Route `PUT /api/user/password` dalam `auth:sanctum`
- `.env.example` + `.env` (lokal) → `SESSION_DRIVER=database`
- `UpdatePasswordTest` (sukses + `Hash::check`; current password salah 422; konfirmasi beda 422; sandi lemah 422; 401 tamu; token lain tercabut)
- Update `003-api-endpoints.md` & `docs/API-Docs-fondasi.md` (bagian Ubah Kata Sandi)
- Commit: `[BE] feat: endpoint ubah kata sandi & pencabutan sesi`

### Tahap B4 — `docs/auth-adjustment` (paling akhir)
- `.agents/PRD.md`: wording ubah kata sandi (baris 61, 135); hapus email (baris 96, 167)
- `.agents/tech-specs/2026/001-architecture.md`: diagram, Notification Strategy, contoh queue, env Mail
- `.agents/tech-specs/2026/002-database-schema.md`: business rule ubah kata sandi / lupa sandi via admin
- `.agents/tech-specs/2026/003-api-endpoints.md`: hapus 1.5–1.7 (email); tandai admin reset sebagai jalur lupa sandi; sesuaikan jumlah endpoint
- `.agents/tasks/backend-roadmap.md`: tulis ulang Task 0.3, hapus Task 7.2, rapikan Task 0.1, tandai status Sprint 0
- `.agents/tech-specs/2026/004-postman-collection-guide.md`: sesuaikan daftar request & skenario error
- `AGENTS.md`: notifikasi database saja; catatan Ubah Kata Sandi
- Commit: `[BE] docs: koreksi autentikasi (ubah kata sandi, tanpa email)`

---

## 5. Verifikasi

Setiap tahap:
- `php artisan test` hijau
- `./vendor/bin/pint` hanya pada file yang disentuh
- `php artisan route:list --path=api` sesuai ekspektasi

Akhir Sprint 0 (setelah semua merged ke develop):
- Route akhir: `POST /api/login`, `POST /api/logout`, `GET /api/user`, `POST /api/register`, `PUT /api/user/password`
- Tidak ada route `forgot-password` / `reset-password`
- `git status` bersih

---

## 6. Definisi Selesai (Definition of Done)

- [ ] Tahap 0 pembersihan selesai
- [ ] B1 test fondasi merged
- [ ] B2 register merged
- [ ] B3 ubah kata sandi merged
- [ ] B4 koreksi dokumentasi merged
- [ ] Semua test hijau, pint bersih, dokumentasi konsisten
