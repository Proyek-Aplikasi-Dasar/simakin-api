# Rencana Sprint 2 — User Detail: Guru, Siswa & Onboarding

**Status:** Rencana disetujui, siap eksekusi
**Periode:** 21–27 Oktober 2026 (5 hari; estimasi 6.5 hari — lihat §2)
**Prasyarat:** Sprint 1 selesai & merged ke `develop`; dokumen onboarding (PRD, 001–003, ERD) sudah diperbarui di branch `docs/siswa-onboarding`
**Goal:** Tabel `guru` & `siswa` lengkap, CRUD + bulk upload Excel, user management, dan onboarding profil siswa (data diri, akademik, orang tua/wali) dengan gate konfirmasi.

---

## 1. Latar Belakang & Keputusan

Sprint 2 mengimplementasikan `backend-roadmap.md` Task 2.1–2.5. Requirement baru: setelah login pertama, siswa mengisi & mengonfirmasi profil. Profil awal **nullable**; setelah konfirmasi hanya admin yang boleh mengubah.

| Topik | Keputusan |
|---|---|
| Identitas login siswa | `username` = NIS; NIS di onboarding read-only, diambil dari `users.username` |
| Field profil siswa | `jurusan_id`, `gelombang_id`, `jenis_kelamin`, `kelas` **nullable**; tambah `alamat`, `alamat_ortu`, `profil_confirmed_at` |
| Email siswa | Disimpan di `users.email` (bukan kolom baru), data kontak — bukan login/notifikasi |
| Tahun ajaran | Ditampilkan dari `gelombang.tahun_ajaran`; tidak ada kolom terpisah |
| Data ortu/wali | Flat column: `nama_ortu`, `telp_ortu`, `alamat_ortu` |
| Konfirmasi | `PUT /api/siswa/me/konfirmasi`; validasi lengkap → set `profil_confirmed_at` |
| Setelah konfirmasi | Siswa **tidak bisa** ubah profil (`PROFIL_SUDAH_DIKONFIRMASI`); hanya admin. Siswa hanya ubah kata sandi |
| Gate | Middleware `EnsureSiswaProfileConfirmed` → 403 `PROFIL_BELUM_KONFIRMASI`; didaftarkan sekarang, diterapkan ke endpoint transaksional Sprint 3+ |
| Otorisasi | Write admin via middleware `role:admin`; siswa hanya endpoint `/siswa/me*` miliknya |
| Seeder | 5 guru + 20 siswa; 1–2 siswa sengaja belum konfirmasi untuk uji onboarding |
| Password seeder | Akun baru: `password123` (didokumentasikan; admin bisa reset) |

---

## 2. Ruang Lingkup

### Termasuk

- Migration/model/seeder `guru` + `siswa` (termasuk kolom onboarding)
- **20 endpoint** Sprint 2 (Guru 6, Siswa 9, User Management 5)
- Onboarding siswa: `GET /siswa/me`, `PUT /siswa/me/profil`, `PUT /siswa/me/konfirmasi`
- Bulk upload Excel guru & siswa (pakai fondasi Sprint 1)
- Middleware gate profil (belum diterapkan ke route aktif)
- Feature test setiap endpoint + skenario onboarding
- Update dokumentasi + Postman

### Tidak Termasuk / Ditunda

- `GET /api/guru/{id}/siswa-bimbingan` → **Sprint 3** (butuh `lokasi_pkl`)
- `GET /api/siswa/{id}/lokasi-pkl` → **Sprint 3** (butuh `lokasi_pkl`)
- Template Excel download (Task 2.5) → boleh digeser ke Sprint 3 bila waktu mepet
- Endpoint transaksional (pengajuan/presensi/jurnal/penilaian) → Sprint 3+

### Ringkasan Endpoint (20)

| # | Resource | Endpoint | Method | Role |
|---|---|---|---|---|
| 2.1 | Guru | `/api/guru` | GET, POST | read admin; write admin |
| | | `/api/guru/{id}` | GET, PUT, DELETE | admin |
| | | `/api/guru/bulk-upload` | POST | admin |
| 2.2 | Siswa | `/api/siswa` | GET, POST | admin |
| | | `/api/siswa/{id}` | GET, PUT, DELETE | admin |
| | | `/api/siswa/bulk-upload` | POST | admin |
| 2.3 | Onboarding | `/api/siswa/me` | GET | siswa |
| | | `/api/siswa/me/profil` | PUT | siswa |
| | | `/api/siswa/me/konfirmasi` | PUT | siswa |
| 2.4 | Users | `/api/users` | GET | admin |
| | | `/api/users/{id}` | GET, PUT, DELETE | admin |
| | | `/api/users/{id}/password` | PUT | admin |

> `/api/siswa/me*` **wajib** didaftarkan sebelum `/api/siswa/{id}`.

---

## 3. Strategi Git

Branch dari `develop`; merge lokal `--no-ff` ke `develop` (tanpa push/PR). Commit `[BE] feat: ...` / `[BE] docs: ...`.

| # | Branch | Isi | Merge ke |
|---|---|---|---|
| B0 | `docs/siswa-onboarding` | Dokumen onboarding (PRD, 001–003, ERD) + rencana ini + panduan Postman | develop |
| B1 | `feature/user-guru` | Migration + Model + Seeder + CRUD + bulk upload Guru | develop |
| B2 | `feature/user-siswa` | Migration + Model + Seeder + CRUD + bulk upload Siswa | develop |
| B3 | `feature/siswa-onboarding` | 3 endpoint onboarding + middleware gate + test | develop |
| B4 | `feature/user-management` | 5 endpoint user management | develop |
| B5 | `feature/excel-templates` | Template Excel (opsional/defer) | develop |
| B6 | `docs/user-detail` | Sinkronisasi dokumentasi + centang roadmap + Postman | develop |

> B2 bergantung B1? Tidak — keduanya hanya butuh `users` (Sprint 0). B3 bergantung B2. B4 independen. B5 independen.

---

## 4. Tahap Pengerjaan

### B0 — `docs/siswa-onboarding`

- Update `PRD.md`, `AGENTS.md`, `docs/to-do-backend-pad-1.txt`
- Update `002-database-schema.md` §11 + `docs/database-erd.xml` (tabel `siswa`)
- Update `003-api-endpoints.md` §1.1 & §10 + Summary; `001-architecture.md` alur mobile + error codes
- Update `backend-roadmap.md`
- Dokumen ini + `postman-onboarding-execution.md`
- Update `docs/API-Docs-fondasi.md`, `004-postman-collection-guide.md`, `postman-auth-execution.md`
- Commit: `[BE] docs: rencana Sprint 2 & onboarding profil siswa`

### B1 — `feature/user-guru` (1.5 hari)

**Migration** `create_guru_table`:
- `user_id` FK unique → `users` (CASCADE), `nip` varchar(30) nullable unique, `mata_pelajaran` varchar(100) nullable, timestamps

**Model** `Guru`: `fillable = [user_id, nip, mata_pelajaran]`; relasi `belongsTo(User)`, `hasMany(LokasiPkl)` (Sprint 3).

**Seeder** `GuruSeeder`: 5 guru (NIP realistis, `username = NIP`), password `password123`; lampirkan profil ke user demo `guru` bila ada.

**Request:**
- `StoreGuruRequest`: `name` required max:255; `username` required unique:users,username (harus = `nip`); `password` required confirmed min:8; `nip` required unique:guru,nip; `mata_pelajaran` nullable max:100
- `UpdateGuruRequest`: sama, `username`/`nip` unique ignore current; password nullable (opsional)

**Logic:**
- Store/update dalam transaction: `users` (role_id=2) + `guru`; `users.name`/`username` mengikuti `name`/`nip`
- Delete: soft delete user? Ikuti konvensi Sprint 1 — hapus `guru` + `users` (CASCADE) atau soft delete `users`; pilih soft delete `users` + `guru` (tanpa `deleted_at` di guru) → gunakan `User::delete()` dan biarkan FK; dokumentasikan.
- Bulk upload: `GuruImport` (kolom: `nip`, `nama`, `mata_pelajaran`; password default `password123`), per baris transaksi, error per baris `{row, error}`

**Routes:** read `admin` (sesuai 003 §9), write `admin`; bulk upload `admin`.

**Test** `tests/Feature/User/GuruTest.php`:
- CRUD sukses 200/201/200; 401 tamu; 403 guru/siswa write
- Auto-create user: `username = nip`, role_id=2
- NIP/username duplikat → 422
- Bulk upload valid + baris invalid + file bukan Excel
- Update tanpa password tidak mengubah password

**Commit:** `[BE] feat: CRUD guru & bulk upload`

### B2 — `feature/user-siswa` (2 hari)

**Migration** `create_siswa_table`:
```php
$table->id();
$table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
$table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->restrictOnDelete();
$table->foreignId('gelombang_id')->nullable()->constrained('gelombang')->restrictOnDelete();
$table->string('nis', 20)->unique();
$table->enum('jenis_kelamin', ['L', 'P'])->nullable();
$table->string('kelas', 20)->nullable();
$table->string('telp', 20)->nullable();
$table->text('alamat')->nullable();
$table->string('nama_ortu', 255)->nullable();
$table->string('telp_ortu', 20)->nullable();
$table->text('alamat_ortu')->nullable();
$table->timestamp('profil_confirmed_at')->nullable()->index();
$table->timestamps();
$table->softDeletes();
```

**Model** `Siswa`: `fillable` semua kolom; relasi `belongsTo(User, Jurusan, Gelombang)`; helper `isProfilLengkap(): bool` (name, jenis_kelamin, kelas, telp, email, alamat, jurusan_id, gelombang_id, nama_ortu, telp_ortu, alamat_ortu).

**Seeder** `SiswaSeeder`: 20 siswa (NIS unik, tersebar di 10 jurusan/2 gelombang), password `password123`; 2 siswa `profil_confirmed_at = null`; lampirkan profil ke user demo `siswa` (belum konfirmasi) agar mobile demo bisa onboarding.

**Request:**
- `StoreSiswaRequest`: `name` required; `nis` required unique:siswa,nis; `username` nullable `same:nis`; `password` required confirmed min:8; `email` nullable email unique:users,email; `jurusan_id`/`gelombang_id` nullable exists; `jenis_kelamin` nullable in:L,P; `kelas` nullable max:20; `telp`/`telp_ortu` nullable max:20; `alamat`/`alamat_ortu` nullable max:1000; `nama_ortu` nullable max:255
- `UpdateSiswaRequest`: sama; `nis`/`username` unik ignore current; password nullable; wajib sinkron `siswa.nis` + `users.username` (transaction)

**Logic:**
- Store: `users` (role_id=3, `username = nis`, `name`, `email`) + `siswa`; default field profil `null`
- Update: update `users.name`, `users.username`, `users.email` + `siswa`; **tidak** reset `profil_confirmed_at`
- Bulk upload `SiswaImport`: kolom `nis`, `nama` wajib; opsional `jenis_kelamin`, `kelas`, `jurusan` (nama/kode), `gelombang` (nama), `telp`, `email`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`; password default `password123`; error per baris

**Routes:** `GET /api/siswa`, `GET /api/siswa/{id}` admin+guru; write admin; bulk upload admin.

**Test** `tests/Feature/User/SiswaTest.php`:
- CRUD + authorization + NIS/username duplikat
- Auto-create user role_id=3, `username = nis`
- Update NIS menyinkronkan username
- Bulk upload valid/invalid + error baris
- Soft delete + relasi user

**Commit:** `[BE] feat: CRUD siswa & bulk upload`

### B3 — `feature/siswa-onboarding` (1.5 hari)

**Endpoints** (di `SiswaController` atau `ProfilSiswaController`):
- `GET /api/siswa/me` (siswa)
- `PUT /api/siswa/me/profil` (siswa)
- `PUT /api/siswa/me/konfirmasi` (siswa)

**`GET /me` response:**
```json
{ "data": {
  "user": { "id": 4, "name": "...", "username": "0012345", "email": null },
  "siswa": { "id": 1, "nis": "0012345", "jenis_kelamin": null, "kelas": null, "telp": null, "alamat": null, "nama_ortu": null, "telp_ortu": null, "alamat_ortu": null },
  "akademik": { "jurusan": null, "gelombang": null },
  "profil_confirmed_at": null,
  "profil_terkonfirmasi": false,
  "profil_lengkap": false
} }
```

**`PUT /me/profil`:**
- Form Request `UpdateProfilSiswaRequest`: hanya field profil (name, jenis_kelamin, telp, email, alamat, kelas, jurusan_id, gelombang_id, nama_ortu, telp_ortu, alamat_ortu); `nis`/`username` **tidak** diterima
- Tolak jika `profil_confirmed_at != null` → 422 `PROFIL_SUDAH_DIKONFIRMASI`
- Update `users.name`, `users.email` + `siswa` dalam transaction
- Response `{ "message": "Profil berhasil diperbarui.", "data": { ... } }`

**`PUT /me/konfirmasi`:**
- Cek `isProfilLengkap()`; jika belum → 422 `{ "message": "...", "code": "PROFIL_BELUM_LENGKAP", "errors": {...} }`
- Jika sudah → set `profil_confirmed_at = now()`; idempotent 200
- Response `{ "message": "Profil berhasil dikonfirmasi.", "data": { ... } }`

**Middleware** `EnsureSiswaProfileConfirmed`:
- Alias `profil.confirmed` di `bootstrap/app.php`
- Cek user role siswa; jika `profil_confirmed_at` null → 403 `{ "message": "Profil belum dikonfirmasi.", "code": "PROFIL_BELUM_KONFIRMASI" }`
- **Belum dipasang** ke route; dipakai Sprint 3+

**Route order** (penting):
```php
Route::middleware(['auth:sanctum', 'role:siswa'])->group(function () {
    Route::get('siswa/me', [SiswaController::class, 'me']);
    Route::put('siswa/me/profil', [SiswaController::class, 'updateProfil']);
    Route::put('siswa/me/konfirmasi', [SiswaController::class, 'konfirmasi']);
});
// baru setelah itu route siswa/{id}
```

**Test** `tests/Feature/User/OnboardingSiswaTest.php`:
- `GET /me` mengembalikan flag benar (belum lengkap/belum konfirmasi)
- Update partial berhasil (profil & user tersimpan)
- Konfirmasi belum lengkap → 422 `PROFIL_BELUM_LENGKAP` (errors field kosong)
- Konfirmasi lengkap → 200 + `profil_confirmed_at` terisi; konfirmasi ulang → 200
- Update setelah konfirmasi → 422 `PROFIL_SUDAH_DIKONFIRMASI`
- Admin tetap bisa update setelah konfirmasi (tidak reset timestamp)
- NIS/username tidak berubah saat update profil
- Siswa lain/admin/guru tidak bisa akses `/siswa/me` (403/401)
- Middleware gate: user belum konfirmasi → 403 `PROFIL_BELUM_KONFIRMASI` (unit/feature test dengan route dummy)

**Commit:** `[BE] feat: onboarding profil siswa & gate konfirmasi`

### B4 — `feature/user-management` (1 hari)

- `GET /api/users` — filter `role_id`, `search` (name/username/email), pagination
- `GET /api/users/{id}` — detail + relasi role/guru/siswa
- `PUT /api/users/{id}` — update name/username/email/role (admin); sinkron profil jika perlu
- `PUT /api/users/{id}/password` — reset password admin; cabut semua token user; tanpa email
- `DELETE /api/users/{id}` — soft delete; cegah admin menghapus dirinya sendiri
- Resource `UserResource` (jangan expose password)
- Test `tests/Feature/User/UserManagementTest.php`: CRUD + reset password + authorization + self-delete guard

**Commit:** `[BE] feat: manajemen user & reset kata sandi`

### B5 — `feature/excel-templates` (0.5 hari, opsional)

- Template siswa: `nis`, `nama`, `jenis_kelamin`, `kelas`, `jurusan`, `gelombang`, `telp`, `email`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`
- Template guru: `nip`, `nama`, `mata_pelajaran`
- Template perusahaan & kompetensi (dari Sprint 1)
- Endpoint download opsional; alternatif file statis di `docs/templates/`
- Test: file bisa diunduh / struktur kolom sesuai

**Commit:** `[BE] feat: template Excel bulk upload`

### B6 — `docs/user-detail` (0.5 hari)

- `002-database-schema.md`: tandai `guru`/`siswa` selesai
- `003-api-endpoints.md`: tandai §9–10 selesai, hitung ulang Summary (37 → 57 done)
- `docs/API-Docs-fondasi.md`: bagian Guru/Siswa/Onboarding
- `backend-roadmap.md`: centang Task 2.1–2.5 + milestone
- `004-postman-collection-guide.md`: status folder 09–10 + panduan eksekusi
- `DatabaseSeeder.php`: register `GuruSeeder`, `SiswaSeeder` (urutan setelah `UserSeeder`, `JurusanSeeder`, `GelombangSeeder`)
- Postman collection + execution docs

**Commit:** `[BE] docs: dokumentasi user detail & onboarding`

---

## 5. Pola Route & Konvensi

```php
Route::middleware('auth:sanctum')->group(function () {
    // Onboarding siswa — HARUS sebelum apiResource siswa
    Route::middleware('role:siswa')->group(function () {
        Route::get('siswa/me', [SiswaController::class, 'me']);
        Route::put('siswa/me/profil', [SiswaController::class, 'updateProfil']);
        Route::put('siswa/me/konfirmasi', [SiswaController::class, 'konfirmasi']);
    });

    // Read
    Route::apiResource('guru', GuruController::class)->only(['index', 'show']);
    Route::apiResource('siswa', SiswaController::class)->only(['index', 'show']);

    // Write admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('guru', GuruController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('siswa', SiswaController::class)->only(['store', 'update', 'destroy']);
        Route::post('guru/bulk-upload', [GuruController::class, 'bulkUpload']);
        Route::post('siswa/bulk-upload', [SiswaController::class, 'bulkUpload']);

        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::put('users/{id}/password', [UserController::class, 'resetPassword']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
    });
});
```

| Aspek | Konvensi |
|---|---|
| Controller | `GuruController`, `SiswaController` (+ method onboarding), `UserController` |
| Request | `app/Http/Requests/User/` (`StoreGuruRequest`, `UpdateGuruRequest`, `StoreSiswaRequest`, `UpdateSiswaRequest`, `UpdateProfilSiswaRequest`, `UpdateUserRequest`, `ResetPasswordRequest`) |
| Resource | `GuruResource`, `SiswaResource`, `UserResource` |
| Response | create `{message, data}` 201; update `{message, data}` 200; list `{data, links, meta}` |
| Delete | 200 `{message: "X berhasil dihapus."}`; FK error → 422 |
| Test | `tests/Feature/User/*`, `RefreshDatabase`, helper `admin()`/`guru()`/`siswa()` |
| Validasi unik | `Rule::unique(...)->ignore($id)` saat update |

---

## 6. Error Codes Sprint 2

| Code | HTTP | Kapan |
|---|---|---|
| `PROFIL_BELUM_KONFIRMASI` | 403 | Gate endpoint transaksional (Sprint 3+) |
| `PROFIL_BELUM_LENGKAP` | 422 | Konfirmasi saat field wajib kosong |
| `PROFIL_SUDAH_DIKONFIRMASI` | 422 | Siswa ubah profil setelah konfirmasi |

---

## 7. Verifikasi (per branch)

1. `php artisan test` — semua test hijau (baseline 136 + test baru)
2. `./vendor/bin/pint` — hanya file yang disentuh
3. `php artisan route:list --path=api` — `/siswa/me*` muncul sebelum `/siswa/{siswa}`
4. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; 5 guru, 20 siswa, 2 belum konfirmasi
5. Cek manual onboarding via Postman (lihat `postman-onboarding-execution.md`)

---

## 8. Definisi Selesai (DoD)

- [ ] B1 Guru merged (migration + model + seeder + CRUD + bulk upload + test)
- [ ] B2 Siswa merged (migration + model + seeder + CRUD + bulk upload + test)
- [ ] B3 Onboarding merged (`/me`, `/me/profil`, `/me/konfirmasi`, gate middleware, test)
- [ ] B4 User management merged (5 endpoint + test)
- [ ] B5 Template Excel (selesai atau defer ke Sprint 3 — dicatat di roadmap)
- [ ] B6 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
- [ ] `GET /api/siswa/me` menampilkan `profil_terkonfirmasi`/`profil_lengkap` dengan benar
- [ ] Profil terkunci setelah konfirmasi; admin tetap bisa mengubah
