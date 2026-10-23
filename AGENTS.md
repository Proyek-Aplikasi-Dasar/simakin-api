<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# SIMAKIN API - Panduan Proyek

Sistem Informasi Manajemen Aktivitas & Kegiatan Industri (PKL) SMK Negeri 1 Magelang.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Database | MySQL 8.0+ |
| Auth | Laravel Sanctum (session web + token mobile) |
| Frontend Web | Vue.js 3 (repo terpisah) |
| Frontend Mobile | Flutter (repo terpisah) |
| File Storage | Local disk |
| Queue | Database driver |
| Environment | Laragon (development) |
| Deployment | Hosting terpisah (API & Web beda domain) |

## Dokumentasi Proyek

| Dokumen | Lokasi |
|---|---|
| PRD | `.agents/PRD.md` |
| Arsitektur | `.agents/tech-specs/2026/001-architecture.md` |
| Skema Database | `.agents/tech-specs/2026/002-database-schema.md` |
| API Endpoints | `.agents/tech-specs/2026/003-api-endpoints.md` |
| Panduan Postman | `.agents/tech-specs/2026/004-postman-collection-guide.md` |
| Roadmap Backend | `.agents/tasks/backend-roadmap.md` |
| API Docs (auth) | `docs/API-Docs-fondasi.md` |
| To-Do PAD 1 | `docs/to-do-backend-pad-1.txt` |
| ERD | `docs/database-erd.xml` + `docs/database-erd.png` |
| MVP | `docs/MVP.txt` |
| Mockups | `docs/mockups/` |

## Development Commands

```sh
# Setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Development
php artisan serve          # http://localhost:8000
php artisan queue:work     # untuk notifikasi database & proses berat (PAD 1 tanpa email)

# Testing
php artisan test
php artisan test --filter=LoginTest

# Code Quality
./vendor/bin/pint          # auto-format PSR-12

# Database
php artisan migrate:status
php artisan migrate:fresh --seed   # HATI-HATI: hapus semua data
php artisan db:seed --class=RoleSeeder

# Routes & Debug
php artisan route:list --path=api
php artisan config:clear
php artisan cache:clear
```

## Default Seeded Users

| Role | Username | Password | Platform |
|---|---|---|---|
| admin | admin | admin123 | Web |
| guru | guru | guru123 | Web |
| siswa | siswa | siswa123 | Mobile |

## Coding Conventions

### Naming

- **Models:** Singular PascalCase (`Siswa`, `LokasiPkl`, `JurnalHarian`)
- **Controllers:** `SiswaController`, `AbsensiController` (resource untuk CRUD simple)
- **Tables:** Singular lowercase (`siswa`, `lokasi_pkl`)
- **Columns:** snake_case (`user_id`, `created_at`)
- **Routes:** kebab-case (`/api/lokasi-pkl`, `/api/waktu-absen`)
- **Pivot:** Alphabetical (`perusahaan_jurusan`)

### Patterns (belum disepakati tim, ikuti use case)

- **Resource Controller** untuk simple CRUD (Jurusan, Kompetensi, Sikap, Waktu Absen)
- **Custom Controller** untuk complex logic (Absensi checkin, Jurnal review, Penilaian)
- **Direct Eloquent** di Controller untuk PAD 1 (Repository pattern opsional PAD 2)

### API Response Format

```php
// Success (single)
return new SiswaResource($siswa);         // { "data": {...} }

// Success (collection)
return SiswaResource::collection($siswa); // { "data": [...], "meta": {...} }

// Success + message
return response()->json([
    'message' => 'Berhasil disimpan.',
    'data' => new SiswaResource($siswa),
], 201);

// Error custom
return response()->json([
    'message' => 'Presensi hari ini sudah dilakukan.',
    'code' => 'ABSENSI_DUPLICATE',
], 422);
```

### Validation

Selalu pakai Form Request:
```sh
php artisan make:request StoreSiswaRequest
```

### Authorization

- **Middleware role:** `Route::middleware(['auth:sanctum', 'role:admin'])`
- **Policy** untuk ownership (siswa edit jurnal miliknya sendiri)
- Role IDs: 1=admin, 2=guru, 3=siswa

### File Upload

- Path: `storage/app/public/uploads/{type}/{YYYY-MM-DD}/`
- Naming: `{user_id}_{timestamp}_{random}.{ext}`
- Validasi: MIME type + max 5MB (foto), 10MB (Excel)
- Selalu `Storage::url($path)` untuk return URL publik

### Testing

- **Feature tests** untuk semua API endpoint
- **Unit tests** untuk business logic kompleks
- Database: SQLite in-memory (sudah dikonfigurasi di `phpunit.xml`)
- Naming: `test_admin_can_create_siswa()`, `test_duplicate_absensi_rejected()`

## Authentication Notes

- **Identitas login: `username` (bukan email).** Aturan username: siswa = NIS, guru = NIP, admin = username kustom. Kolom `email` tetap ada tetapi nullable dan tidak dipakai untuk login.
- **Web (admin/guru):** Session + CSRF cookie. `GET /sanctum/csrf-cookie` dulu, lalu login tanpa `X-Client`.
- **Mobile (siswa):** Bearer token. Login dengan header `X-Client: mobile`, simpan token, kirim `Authorization: Bearer {token}`.
- Siswa **tidak bisa login via web** (dicek di LoginController).
- **Onboarding siswa:** setelah login pertama, siswa wajib melengkapi & mengonfirmasi profil (data diri, akademik, orang tua/wali) via `GET /api/siswa/me`, `PUT /api/siswa/me/profil`, dan `PUT /api/siswa/me/konfirmasi`. NIS diambil dari `users.username` dan read-only. Setelah `profil_confirmed_at` terisi, siswa **tidak bisa** mengubah profil; perubahan hanya oleh admin. Siswa tetap bisa mengubah kata sandi sendiri.

## Error Codes Kustom

| Code | HTTP | Kapan |
|---|---|---|
| `ABSENSI_DUPLICATE` | 422 | Sudah presensi hari ini |
| `ABSENSI_REQUIRED` | 422 | Belum presensi, mau bikin jurnal |
| `JURNAL_NOT_EDITABLE` | 422 | Jurnal sudah di-approve guru |
| `PERIODE_CLOSED` | 422 | Periode penilaian tidak aktif |
| `PENGAJUAN_DUPLICATE` | 422 | Sudah ada pengajuan pending |
| `PERUSAHAAN_KUOTA_PENUH` | 422 | Kuota perusahaan habis |
| `SISWA_ALREADY_PLACED` | 422 | Siswa sudah punya PKL aktif |
| `GELOMBANG_NOT_ACTIVE` | 422 | Pendaftaran gelombang tertutup |
| `PROFIL_BELUM_KONFIRMASI` | 403 | Profil siswa belum dikonfirmasi (gate endpoint transaksional) |
| `PROFIL_BELUM_LENGKAP` | 422 | Data profil belum lengkap saat konfirmasi |
| `PROFIL_SUDAH_DIKONFIRMASI` | 422 | Siswa mencoba mengubah profil setelah konfirmasi (hanya admin) |

## Git Workflow

- Branch: `main` (stable), `develop` (staging), `feature/*`, `bugfix/*`
- Commit: `[BE] feat: deskripsi` / `[BE] fix: deskripsi` / `[BE] docs: deskripsi`
- PR ke `develop` untuk review, merge ke `main` untuk stable

## Key Constraints

- **Deadline PAD 1:** 12 Desember 2026
- **Scope PAD 1:** Sesuai `docs/to-do-backend-pad-1.txt` (tanpa chat)
- **Siswa:** 700+ per angkatan, 1 siswa = 1 gelombang
- **Presensi:** Hanya check-in (tanpa jam keluar), rekam GPS tanpa validasi radius
- **Registrasi siswa:** Admin only (bukan self-register)
- **Onboarding siswa:** Profil awal nullable; siswa mengisi data diri, akademik, dan orang tua/wali lalu konfirmasi. Setelah konfirmasi, profil hanya bisa diubah admin (validasi sekolah); siswa hanya bisa mengubah kata sandi
- **Email siswa:** Disimpan di `users.email` sebagai data kontak, bukan identitas login/notifikasi
- **Excel:** Bulk upload (siswa, guru, perusahaan, kompetensi) + export rekap
- **Notifikasi:** Database saja via queue (tanpa email & push notification di PAD 1)
- **Lupa sandi:** Hubungi admin; admin reset kata sandi user (tanpa reset via email)
- **Watermark foto penilaian:** Dilakukan di mobile app (Flutter), backend simpan metadata GPS
