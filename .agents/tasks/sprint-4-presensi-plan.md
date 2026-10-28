# Rencana Sprint 4 — Presensi Harian

**Status:** Rencana disetujui, siap eksekusi
**Periode:** 4–10 November 2026 (5 hari; estimasi 4.5 hari — lihat §2)
**Prasyarat:** Sprint 3 selesai & merged ke `develop`; tabel `siswa`, `gelombang`, `lokasi_pkl`, `waktu_absen` tersedia; middleware `profil.confirmed` aktif
**Goal:** Siswa bisa check-in harian dengan foto + GPS, admin/guru bisa koreksi manual, rekap presensi tersedia, dan integrasi ke dashboard admin + siswa bimbingan guru.

---

## 1. Latar Belakang & Keputusan

Sprint 4 mengimplementasikan `backend-roadmap.md` Task 4.1–4.5. Requirement baru: presensi **hanya check-in** (tanpa jam/foto keluar), GPS direkam tanpa validasi radius, foto wajib untuk status `hadir`, dan rekap agregat per siswa.

| Topik | Keputusan |
|---|---|
| Identitas presensi | `siswa_id` diambil dari user login (role siswa), tidak dari body |
| Jam masuk | **Server-side** (`now()->format('H:i:s')`), bukan dari client |
| Status kehadiran | Enum `hadir`, `izin`, `sakit`, `libur` (default `hadir`) |
| Foto (hadir) | Wajib, maks 5MB, `jpeg/jpg/png`, simpan di `storage/app/public/uploads/presensi/{YYYY-MM-DD}/{user_id}_{timestamp}_{random}.{ext}`, return `Storage::url($path)` |
| Foto (izin/sakit/libur) | Opsional, `keterangan` wajib |
| Keterlambatan | Dihitung berdasarkan `waktu_absen.mulai` (Sprint 7 rekap) |
| Anti-double submit | `UNIQUE(siswa_id, tanggal)` + handling `QueryException` → `422 ABSENSI_DUPLICATE` |
| Edit presensi | Hanya jika `status_submit = 'failed'` → `ABSENSI_EDIT_BLOCKED` jika sudah `success` |
| Retry foto | `POST /api/absensi/upload-foto` (partial save) |
| Batalkan presensi | `DELETE /api/absensi/{id}` oleh admin atau siswa pemilik (hapus file + record) |
| Koreksi manual | `POST /api/absensi/{id}/koreksi` oleh admin/guru (guru hanya siswa bimbingannya); set `dikoreksi_oleh`, kirim notifikasi `absensi-corrected` |
| Rekap | `GET /api/absensi/rekap` (agregat per siswa) + `GET /api/absensi/siswa/{id}/periode` (bulanan + rincian harian) |
| Gate onboarding | Middleware `profil.confirmed` pada `POST /api/absensi` dan `POST /api/absensi/upload-foto` |
| Gate PKL aktif | Siswa harus punya `lokasi_pkl` active dan tanggal presensi dalam periode `praktik_mulai`..`praktik_selesai` gelombang (`GELOMBANG_NOT_ACTIVE`) |
| Integrasi dashboard | `GET /api/dashboard/admin` mengganti placeholder `presensi_hari_ini` dengan hitungan nyata |
| Integrasi siswa bimbingan | `GET /api/guru/{id}/siswa-bimbingan` mengisi `presensi_hari_ini` (`status`, `jam_masuk`) berdasarkan query `?tanggal=` (default hari ini) |
| Status submit | `success` (default) / `failed` (partial save, retry diizinkan) |

---

## 2. Ruang Lingkup

### Termasuk

- Migration/model/seeder `absensi_siswa`
- **9 endpoint** baru: list, rekap, periode, upload-foto, detail, check-in, edit, delete, koreksi
- File upload foto ke `storage/app/public/uploads/presensi/{date}/`
- Anti-double submit + retry + partial save
- Koreksi manual + notifikasi database ke siswa
- Rekap agregat per siswa & rekap bulanan per siswa
- Integrasi ke `GuruController@siswaBimbingan` dan `DashboardController@admin`
- Feature test setiap endpoint + skenario error + integrasi
- Update dokumentasi + Postman

### Tidak Termasuk / Ditunda

- Validasi radius GPS (tidak divalidasi, hanya direkam) — sesuai konfirmasi
- Jam keluar / foto keluar (tidak ada)
- Jurnal harian → Sprint 5
- Dashboard guru & siswa (Sprint 7, butuh data presensi/jurnal/penilaian)
- Rekap Excel → Sprint 7

### Ringkasan Endpoint (9 baru + 2 integrasi)

| # | Method & Endpoint | Role | Keterangan |
|---|---|---|---|
| 13 | `GET /api/absensi` | admin, guru, siswa | List (filter `siswa_id`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `status`, `page`). Siswa hanya miliknya. |
| | `GET /api/absensi/rekap` | admin, guru | Rekap agregat per siswa (filter `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`). |
| | `GET /api/absensi/siswa/{siswa}/periode` | admin, guru, siswa | Rekap bulanan 1 siswa (`?bulan=`, `?tahun=`). Siswa hanya miliknya. |
| | `POST /api/absensi/upload-foto` | siswa (`profil.confirmed`) | Retry upload foto terpisah. |
| | `GET /api/absensi/{absensi}` | admin, guru, siswa | Detail absensi (siswa hanya miliknya). |
| | `POST /api/absensi` | siswa (`profil.confirmed`) | Check-in (multipart foto + GPS + keterangan). |
| | `PUT /api/absensi/{absensi}` | siswa (`profil.confirmed`) | Edit jika `status_submit = 'failed'`. |
| | `DELETE /api/absensi/{absensi}` | admin, siswa | Batalkan presensi. |
| | `POST /api/absensi/{absensi}/koreksi` | admin, guru | Koreksi manual + notifikasi ke siswa. |
| *Integrasi* | `GET /api/guru/{guru}/siswa-bimbingan` | admin, guru | Populate `presensi_hari_ini` (status, jam_masuk). |
| *Integrasi* | `GET /api/dashboard/admin` | admin | Populate `presensi_hari_ini` (`hadir`, `izin`, `sakit`, `tidak_absen`). |

> **Urutan route penting:** `/api/absensi/rekap`, `/api/absensi/siswa/{siswa}/periode`, `/api/absensi/upload-foto` **wajib** didaftarkan sebelum `/api/absensi/{absensi}`.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B3)

Mengikuti pola Sprint 1–3 (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/sprint-4-plan`**
- Buat dokumen rencana ini (`.agents/tasks/sprint-4-presensi-plan.md`).
- Commit: `[BE] docs: rencana Sprint 4 presensi harian`.

### **Tahap B1 — `feature/absensi-checkin` (Estimasi: 2 hari)**
1. **Migration:** `create_absensi_siswa_table` (lengkap dengan `unique(['siswa_id', 'tanggal'])`).
2. **Model:** `AbsensiSiswa` + relasi `siswa()`, `korektor()` (`belongsTo(User, 'dikoreksi_oleh')`), serta relasi `Siswa::absensi()` (`hasMany`).
3. **Request & Resource:**
   - `StoreAbsensiRequest`, `UpdateAbsensiRequest`, `UploadFotoAbsensiRequest` di `app/Http/Requests/Absensi/`.
   - `AbsensiSiswaResource` (menyertakan URL foto publik via `Storage::url()`).
4. **Controller (`AbsensiSiswaController`):**
   - `index`, `show`, `store` (check-in + simpan foto + server timestamp + `next_action`), `uploadFoto`, `update` (hanya `status_submit = 'failed'`), `destroy`.
5. **Seeder:** `AbsensiSiswaSeeder` (sample presensi beberapa hari untuk siswa yang punya `lokasi_pkl` aktif).
6. **Test:** `tests/Feature/Absensi/AbsensiCheckinTest.php` (`Storage::fake('public')`) — uji check-in hadir dengan foto, izin/sakit tanpa foto + wajib keterangan, gate `PROFIL_BELUM_KONFIRMASI`, `GELOMBANG_NOT_ACTIVE`, `ABSENSI_DUPLICATE`, retry `upload-foto`, edit saat `failed` vs ditolak saat `success` (`ABSENSI_EDIT_BLOCKED`), dan delete.
7. **Commit:** `[BE] feat: tabel & check-in presensi harian siswa`.

### **Tahap B2 — `feature/absensi-koreksi-rekap` (Estimasi: 1.5 hari)**
1. **Request & Notification:**
   - `KoreksiAbsensiRequest` (`status`, `keterangan` required, `jam_masuk` nullable `date_format:H:i,H:i:s`).
   - `AbsensiCorrectedNotification` (channel `database`, type `absensi-corrected`).
2. **Controller (`AbsensiSiswaController` lanjutan):**
   - `koreksi`: update presensi, set `dikoreksi_oleh`, kirim notifikasi ke user siswa.
   - `rekap`: agregasi `hadir`, `izin`, `sakit`, `libur`, `tidak_absen` per siswa (filter `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`).
   - `periodeSiswa`: rekap bulanan + rincian harian untuk 1 siswa (`bulan`, `tahun`).
3. **Integrasi Endpoint Existing:**
   - Update `GuruController@siswaBimbingan` agar mengisi `presensi_hari_ini`.
   - Update `DashboardController@admin` agar menghitung `presensi_hari_ini` nyata.
4. **Test:** `tests/Feature/Absensi/AbsensiKoreksiRekapTest.php` — koreksi oleh admin/guru, otorisasi guru bukan pembimbing ditolak, notifikasi terkirim, akurasi angka `/absensi/rekap` & `/absensi/siswa/{id}/periode`, serta integrasi di `siswa-bimbingan` & `dashboard/admin`.
5. **Commit:** `[BE] feat: koreksi presensi, rekap & integrasi dashboard`.

### **Tahap B3 — `docs/sprint-4-presensi` (Estimasi: 0.5 hari)**
1. Update `.agents/tech-specs/2026/002-database-schema.md` (tandai `absensi_siswa` ✅; total **15 sudah ada, 5 belum**).
2. Update `.agents/tech-specs/2026/003-api-endpoints.md` (tandai §13 Presensi 9 endpoint ✅; update Summary **73 → 82 done**).
3. Update `.agents/tasks/backend-roadmap.md` (centang Task 4.1–4.5).
4. Update `docs/API-Docs-fondasi.md` (tambah bagian Presensi Harian).
5. Buat `.agents/tasks/postman-sprint4-execution.md` & update `docs/postman/` (collection + environment JSON).
6. Daftarkan `AbsensiSiswaSeeder` di `DatabaseSeeder.php`.
7. **Commit:** `[BE] docs: dokumentasi presensi harian & koleksi Postman Sprint 4`.

---

## 5. Pola Route & Konvensi

```php
Route::middleware('auth:sanctum')->group(function () {
    // ... (route Sprint 1-3 tetap)

    // Absensi - read (admin/guru/siswa)
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('absensi', [AbsensiSiswaController::class, 'index']);
        Route::get('absensi/rekap', [AbsensiSiswaController::class, 'rekap']);
        Route::get('absensi/siswa/{siswa}/periode', [AbsensiSiswaController::class, 'periodeSiswa']);
        Route::get('absensi/{absensi}', [AbsensiSiswaController::class, 'show']);
    });

    // Absensi - siswa transaksional (gate profil)
    Route::middleware(['role:siswa', 'profil.confirmed'])->group(function () {
        Route::post('absensi', [AbsensiSiswaController::class, 'store']);
        Route::put('absensi/{absensi}', [AbsensiSiswaController::class, 'update']);
        Route::post('absensi/upload-foto', [AbsensiSiswaController::class, 'uploadFoto']);
    });

    // Absensi - delete (admin/siswa)
    Route::middleware('role:admin,siswa')->group(function () {
        Route::delete('absensi/{absensi}', [AbsensiSiswaController::class, 'destroy']);
    });

    // Absensi - koreksi (admin/guru)
    Route::middleware('role:admin,guru')->group(function () {
        Route::post('absensi/{absensi}/koreksi', [AbsensiSiswaController::class, 'koreksi']);
    });
});
```

| Aspek | Konvensi |
|---|---|
| Controller | `AbsensiSiswaController` |
| Request | `app/Http/Requests/Absensi/` (`StoreAbsensiRequest`, `UpdateAbsensiRequest`, `UploadFotoAbsensiRequest`, `KoreksiAbsensiRequest`) |
| Resource | `AbsensiSiswaResource` |
| Notification | `App\Notifications\AbsensiCorrectedNotification` |
| Response | check-in `{success, message, data, next_action}` 201; list `{data, links, meta}` |
| File upload | `Storage::disk('public')`, path `uploads/presensi/{date}/{user_id}_{timestamp}_{random}.{ext}` |
| Test | `tests/Feature/Absensi/*`, `RefreshDatabase`, `Storage::fake('public')` |

---

## 6. Error Codes Sprint 4

| Code | HTTP | Kapan |
|---|---|---|
| `ABSENSI_DUPLICATE` | 422 | Sudah presensi hari ini |
| `ABSENSI_EDIT_BLOCKED` | 422 | Edit presensi yang sudah `success` |
| `GELOMBANG_NOT_ACTIVE` | 422 | Presensi di luar periode praktik gelombang |
| `PROFIL_BELUM_KONFIRMASI` | 403 | Siswa belum konfirmasi profil |

---

## 7. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama (245) + test baru Sprint 4 hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan route:list --path=api/absensi` — route statis terdaftar sebelum `{absensi}`.
4. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; sample presensi tersedia.
5. `php artisan storage:link` — symlink `public/storage` aktif (untuk `Storage::url()`).

---

## 8. Definisi Selesai (DoD)

- [ ] B1 Check-in merged (migration + model + seeder + controller + resource + test)
- [ ] B2 Koreksi & rekap merged (koreksi + notifikasi + rekap + periode + integrasi dashboard/siswa-bimbingan + test)
- [ ] B3 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
- [ ] `GET /api/absensi` mengembalikan `next_action: /jurnal/create` saat status `hadir`
- [ ] `GET /api/guru/{id}/siswa-bimbingan` menampilkan `presensi_hari_ini` nyata
- [ ] `GET /api/dashboard/admin` menampilkan `presensi_hari_ini` nyata (bukan placeholder)

---

Apakah rencana Sprint 4 ini sudah sesuai dengan ekspektasi Anda? Jika sudah setuju, silakan beralih dari **Plan mode** ke **Build mode** agar kita bisa mulai dari tahap **B0 (`docs/sprint-4-plan`)**.