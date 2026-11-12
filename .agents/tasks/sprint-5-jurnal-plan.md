# Rencana Sprint 5 — Jurnal Harian

**Status:** Rencana disetujui, siap eksekusi
**Periode:** 11–17 November 2026 (5 hari; estimasi 4 hari — lihat §2)
**Prasyarat:** Sprint 4 selesai & merged ke `develop`; tabel `siswa`, `kompetensi`, `absensi_siswa`, `lokasi_pkl` tersedia; middleware `profil.confirmed` aktif
**Goal:** Siswa dapat mencatat jurnal kegiatan harian setelah presensi `hadir` (tanpa approval guru), siswa dapat mengedit/menghapus jurnal miliknya sendiri, admin dapat mengedit/menghapus seluruh jurnal, guru dapat memantau secara read-only, dilengkapi riwayat kronologis, rekap jurnal, dan integrasi ke siswa bimbingan guru.

---

## 1. Latar Belakang & Keputusan

Sprint 5 mengimplementasikan `backend-roadmap.md` Task 5.1–5.4 berdasarkan revisi spesifikasi UI/UX tanggal 5 November 2026: **approval/review jurnal oleh guru dihapus** untuk menyederhanakan alur kerja.

| Topik | Keputusan |
|---|---|
| Penghapusan approval guru | Kolom `status_review`, `catatan_guru`, `reviewed_by`, `reviewed_at`, `is_editable` **dihapus** dari skema `jurnal_harian` dan diganti dengan `edited_by` (FK `users`) serta `edited_at` (`timestamp`) |
| Identitas pembuat jurnal | `siswa_id` diambil dari user login (role `siswa`), tidak dikirim dari body |
| Syarat presensi hadir | Saat `POST /api/jurnal`, siswa wajib sudah memiliki record `absensi_siswa` pada `tanggal` terkait dengan `status = 'hadir'`; jika belum presensi atau berstatus `izin`/`sakit`/`libur` → `422 ABSENSI_REQUIRED` |
| Anti-double submit | `UNIQUE(siswa_id, tanggal)` (`jurnal_unique_siswa_tanggal`) — maksimal 1 jurnal per siswa per tanggal |
| Deskripsi kegiatan | Tipe `text` (karakter tidak dibatasi untuk mengakomodasi laporan lengkap siswa) |
| Foto dokumentasi | Opsional saat `POST`/`PUT`, atau diunggah terpisah via `POST /api/jurnal/upload-foto` (maks 5MB, `jpeg/jpg/png`), disimpan di `storage/app/public/uploads/jurnal/{YYYY-MM-DD}/{user_id}_{timestamp}_{random}.{ext}`, dikembalikan via `Storage::url($path)` |
| Otorisasi edit & hapus | `JurnalHarianPolicy`: siswa hanya boleh mengubah/menghapus jurnal miliknya sendiri kapan saja; admin boleh mengubah/menghapus semua jurnal; guru bersifat read-only (`403` jika mencoba `PUT`/`DELETE`) |
| Tracking perubahan | Setiap `PUT /api/jurnal/{jurnal}` otomatis mengisi `edited_by = auth()->id()` dan `edited_at = now()` |
| Riwayat & rekap | `GET /api/jurnal/riwayat` (urut kronologis `sort=asc\|desc` per tanggal) & `GET /api/jurnal/rekap` (total jurnal per siswa tanpa breakdown status review) |
| Gate onboarding | Middleware `profil.confirmed` dipasang pada endpoint transaksional siswa (`POST /api/jurnal`, `POST /api/jurnal/upload-foto`, dan `PUT /api/jurnal/{jurnal}` jika diakses oleh siswa) |
| Integrasi siswa bimbingan | `GET /api/guru/{guru}/siswa-bimbingan` mengisi `jurnal_hari_ini` berdasarkan query `?tanggal=` (default hari ini) |

---

## 2. Ruang Lingkup

### Termasuk

- Migration/model/policy/seeder `jurnal_harian`
- **8 endpoint** baru: list, riwayat, rekap, upload-foto, detail, create, update, delete
- File upload foto dokumentasi ke `storage/app/public/uploads/jurnal/{date}/` + retry `upload-foto`
- Validasi presensi `hadir` (`ABSENSI_REQUIRED`) + constraint 1 jurnal per hari per siswa
- Tracking `edited_by` & `edited_at` saat edit jurnal oleh siswa maupun admin
- Integrasi `jurnal_hari_ini` pada `GuruController@siswaBimbingan`
- Penanganan FK `RESTRICT` saat menghapus `kompetensi` yang sudah direferensikan `jurnal_harian`
- Feature test setiap endpoint + skenario otorisasi/error + integrasi
- Update dokumentasi + Postman

### Tidak Termasuk / Ditunda

- Endpoint `/approve` dan `/revise` jurnal (dihapus sesuai keputusan revisi 5 Nov 2026)
- Penilaian bulanan → Sprint 6
- Dashboard guru & siswa → Sprint 7
- Export rekap Excel → Sprint 7

### Ringkasan Endpoint (8 baru + 1 integrasi)

| # | Method & Endpoint | Role | Keterangan |
|---|---|---|---|
| 14 | `GET /api/jurnal` | admin, guru, siswa | List jurnal (filter `siswa_id`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `kompetensi_id`, `page`). Siswa hanya miliknya. |
| | `GET /api/jurnal/riwayat` | admin, guru, siswa | Riwayat kronologis (`?siswa_id=&tanggal_mulai=&tanggal_selesai=&sort=asc\|desc`). Siswa hanya miliknya. |
| | `GET /api/jurnal/rekap` | admin, guru | Rekap total jurnal per siswa (`?gelombang_id=&siswa_id=&tanggal_mulai=&tanggal_selesai=`). |
| | `POST /api/jurnal/upload-foto` | siswa (`profil.confirmed`) | Retry/upload foto dokumentasi terpisah (`jurnal_id`, `foto`). |
| | `GET /api/jurnal/{jurnal}` | admin, guru, siswa | Detail jurnal (siswa hanya miliknya). |
| | `POST /api/jurnal` | siswa (`profil.confirmed`) | Create jurnal (wajib sudah presensi `hadir` pada tanggal tersebut). |
| | `PUT /api/jurnal/{jurnal}` | admin, siswa (`profil.confirmed`) | Edit jurnal (siswa pemilik atau admin); set `edited_by` & `edited_at`. |
| | `DELETE /api/jurnal/{jurnal}` | admin, siswa | Hapus jurnal (siswa pemilik atau admin) + hapus file foto. |
| *Integrasi* | `GET /api/guru/{guru}/siswa-bimbingan` | admin, guru | Populate `jurnal_hari_ini` dari `jurnal_harian` pada tanggal query. |

> **Urutan route penting:** `/api/jurnal/riwayat`, `/api/jurnal/rekap`, dan `/api/jurnal/upload-foto` **wajib** didaftarkan sebelum `/api/jurnal/{jurnal}`.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B3)

Mengikuti pola Sprint 1–4 (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/sprint-5-plan`**
- Buat dokumen rencana ini (`.agents/tasks/sprint-5-jurnal-plan.md`).
- Commit: `[BE] docs: rencana Sprint 5 jurnal harian`.

### **Tahap B1 — `feature/jurnal-crud` (Estimasi: 2 hari)**
1. **Migration:** `create_jurnal_harian_table` (`siswa_id` FK cascade + index, `kompetensi_id` FK restrict, `tanggal` date + index, `deskripsi` text, `foto` varchar(500) nullable, `edited_by` FK `users` nullOnDelete nullable, `edited_at` timestamp nullable, timestamps, serta `unique(['siswa_id', 'tanggal'])`).
2. **Model & Policy:**
   - `JurnalHarian` + relasi `siswa()`, `kompetensi()`, `editor()` (`belongsTo(User, 'edited_by')`).
   - Tambah relasi `Siswa::jurnalHarian()` (`hasMany`) & `Kompetensi::jurnalHarian()` (`hasMany`).
   - `JurnalHarianPolicy` (`view`, `update`, `delete` untuk siswa pemilik atau admin).
3. **Request & Resource:**
   - `StoreJurnalRequest`, `UpdateJurnalRequest`, `UploadFotoJurnalRequest` di `app/Http/Requests/Jurnal/`.
   - `JurnalHarianResource` (memuat `siswa`, `kompetensi`, `editor`, `foto` URL publik via `Storage::url()`).
4. **Controller (`JurnalHarianController`):**
   - `index`, `show`, `store` (validasi presensi `hadir` → `ABSENSI_REQUIRED`, cegah duplikat tanggal, simpan foto opsional), `update` (siswa pemilik / admin, set `edited_by` & `edited_at`), `destroy`, `uploadFoto`.
5. **Seeder:** `JurnalHarianSeeder` (sample jurnal untuk siswa yang memiliki presensi `hadir` dari `AbsensiSiswaSeeder`).
6. **Test:** `tests/Feature/Jurnal/JurnalCrudTest.php` (`Storage::fake('public')`) — uji create sukses setelah presensi hadir, ditolak jika belum presensi / presensi izin (`ABSENSI_REQUIRED`), duplikat tanggal ditolak, gate `PROFIL_BELUM_KONFIRMASI`, upload foto & retry `upload-foto`, siswa edit/delete milik sendiri, admin edit/delete jurnal siapa saja, guru/siswa lain ditolak (403), serta `edited_by` & `edited_at` tercatat.
7. **Commit:** `[BE] feat: tabel & CRUD jurnal harian siswa dan admin`.

### **Tahap B2 — `feature/jurnal-riwayat-rekap` (Estimasi: 1 hari)**
1. **Controller (`JurnalHarianController` lanjutan):**
   - `riwayat`: daftar kronologis per tanggal (`sort` `asc`/`desc`, filter `siswa_id`, `tanggal_mulai`, `tanggal_selesai`; siswa hanya miliknya).
   - `rekap`: agregasi total jurnal per siswa (filter `gelombang_id`, `siswa_id`, `tanggal_mulai`, `tanggal_selesai`).
2. **Integrasi Endpoint Existing:**
   - Update `GuruController@siswaBimbingan` agar mengisi `jurnal_hari_ini` pada tanggal yang diminta.
   - Pastikan penghapusan `Kompetensi` yang sudah dipakai di `jurnal_harian` mengembalikan respons `422` yang ramah (FK `RESTRICT` di `KompetensiController@destroy`).
3. **Test:** `tests/Feature/Jurnal/JurnalRiwayatRekapTest.php` — urutan kronologis `asc`/`desc`, isolasi data siswa pada `riwayat`, akurasi perhitungan `rekap`, otorisasi `rekap` (siswa → 403), FK restrict `kompetensi`, dan integrasi `jurnal_hari_ini` pada `siswa-bimbingan`.
4. **Commit:** `[BE] feat: riwayat, rekap jurnal harian & integrasi siswa bimbingan`.

### **Tahap B3 — `docs/sprint-5-jurnal` (Estimasi: 0.5 hari)**
1. Update `.agents/tech-specs/2026/002-database-schema.md` (tandai `jurnal_harian` ✅; total **16 sudah ada, 4 belum**).
2. Update `.agents/tech-specs/2026/003-api-endpoints.md` (tandai §14 Jurnal Harian 8 endpoint ✅; update Summary **82 → 90 done**).
3. Update `.agents/tasks/backend-roadmap.md` (centang Task 5.1–5.4).
4. Update `docs/API-Docs-fondasi.md` (tambah bagian Jurnal Harian).
5. Buat `.agents/tasks/postman-sprint5-execution.md` & update `docs/postman/` (collection + environment JSON).
6. Daftarkan `JurnalHarianSeeder` di `DatabaseSeeder.php`.
7. **Commit:** `[BE] docs: dokumentasi jurnal harian & koleksi Postman Sprint 5`.

---

## 4. Pola Route & Konvensi

```php
Route::middleware('auth:sanctum')->group(function () {
    // ... (route Sprint 1-4 tetap)

    // Jurnal Harian - read (admin, guru, siswa)
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('jurnal', [JurnalHarianController::class, 'index']);
        Route::get('jurnal/riwayat', [JurnalHarianController::class, 'riwayat']);
        Route::get('jurnal/{jurnal}', [JurnalHarianController::class, 'show']);
    });

    // Jurnal Harian - rekap (admin, guru)
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('jurnal/rekap', [JurnalHarianController::class, 'rekap']);
    });

    // Jurnal Harian - siswa transaksional (gate profil)
    Route::middleware(['role:siswa', 'profil.confirmed'])->group(function () {
        Route::post('jurnal', [JurnalHarianController::class, 'store']);
        Route::post('jurnal/upload-foto', [JurnalHarianController::class, 'uploadFoto']);
    });

    // Jurnal Harian - update & delete (admin atau siswa pemilik)
    Route::middleware(['role:admin,siswa', 'profil.confirmed'])->group(function () {
        Route::put('jurnal/{jurnal}', [JurnalHarianController::class, 'update']);
        Route::delete('jurnal/{jurnal}', [JurnalHarianController::class, 'destroy']);
    });
});
```

| Aspek | Konvensi |
|---|---|
| Controller | `JurnalHarianController` |
| Policy | `app/Policies/JurnalHarianPolicy.php` |
| Request | `app/Http/Requests/Jurnal/` (`StoreJurnalRequest`, `UpdateJurnalRequest`, `UploadFotoJurnalRequest`) |
| Resource | `JurnalHarianResource` |
| Response | create `{message, data}` 201; update `{message, data}` 200; list `{data, links, meta}` |
| File upload | `Storage::disk('public')`, path `uploads/jurnal/{date}/{user_id}_{timestamp}_{random}.{ext}` |
| Test | `tests/Feature/Jurnal/*`, `RefreshDatabase`, `Storage::fake('public')` |

---

## 5. Error Codes Sprint 5

| Code | HTTP | Kapan |
|---|---|---|
| `ABSENSI_REQUIRED` | 422 | Siswa membuat jurnal tetapi belum presensi `hadir` pada tanggal tersebut |
| `PROFIL_BELUM_KONFIRMASI` | 403 | Siswa belum mengonfirmasi profil saat mengakses endpoint transaksional |

---

## 6. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama + test baru Sprint 5 hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan route:list --path=api/jurnal` — route statis (`riwayat`, `rekap`, `upload-foto`) terdaftar sebelum `{jurnal}`.
4. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; sample jurnal tersedia.

---

## 7. Definisi Selesai (DoD)

- [ ] B1 CRUD Jurnal merged (migration + model + policy + seeder + controller + resource + test)
- [ ] B2 Riwayat, rekap & integrasi merged (`riwayat` + `rekap` + integrasi `siswa-bimbingan` + FK restrict `kompetensi` + test)
- [ ] B3 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
- [ ] `POST /api/jurnal` menolak input jika belum presensi `hadir` (`422 ABSENSI_REQUIRED`)
- [ ] `PUT /api/jurnal/{id}` mencatat `edited_by` & `edited_at` baik saat diedit siswa maupun admin
- [ ] `GET /api/guru/{id}/siswa-bimbingan` menampilkan `jurnal_hari_ini` nyata
