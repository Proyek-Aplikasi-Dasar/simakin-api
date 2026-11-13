# Rencana Sprint 4 — Presensi Harian

**Status:** Selesai (B0–B3) + **Update Tindak Lanjut Mockup Admin (B4–B5)**
**Periode:** 4–10 November 2026 (lanjutan review 13 November 2026)
**Prasyarat:** Sprint 3 selesai & merged ke `develop`; tabel `siswa`, `gelombang`, `lokasi_pkl`, `waktu_absen` tersedia; middleware `profil.confirmed` aktif
**Goal:** Siswa bisa check-in harian dengan foto + GPS (termasuk jam shift & berkas pendukung izin), admin/guru bisa **menambah presensi manual**, **mengoreksi presensi**, serta **mengekspor data presensi ke Excel**, dilengkapi rekap presensi dan integrasi ke dashboard admin + siswa bimbingan guru.

---

## 1. Latar Belakang & Keputusan

Sprint 4 mengimplementasikan `backend-roadmap.md` Task 4.1–4.8 ditambah hasil review mockup `docs/mockups/web/admin/monitoring/presensi-jurnal/presensi-siswa/admin-presensi-siswa.png` dan `admin-presensi-siswa-form-tambah-koreksi.png`.

| Topik | Keputusan |
|---|---|
| Identitas presensi (Mobile Siswa) | `siswa_id` diambil dari user login (role `siswa`), tidak dari body |
| Jam masuk & Jam shift | `jam_masuk` dicatat **server-side** (`now()->format('H:i:s')`) saat siswa check-in; siswa maupun admin dapat mengisi `jam_shift_mulai` & `jam_shift_selesai` (opsional, format `H:i`) |
| Status kehadiran | Enum `hadir`, `izin`, `sakit`, `libur` (default `hadir`) |
| Foto (`hadir` - Siswa) | Wajib saat check-in siswa, maks 5MB, `jpeg/jpg/png`, simpan di `storage/app/public/uploads/presensi/{YYYY-MM-DD}/{user_id}_{timestamp}_{random}.{ext}`, return `Storage::url($path)` |
| Foto & Berkas (`izin`/`sakit`/`libur`) | `foto_masuk` opsional, `keterangan` wajib, `berkas_pendukung` opsional (`pdf,jpeg,jpg,png` maks 5MB di `uploads/presensi/berkas/{YYYY-MM-DD}/`) |
| Anti-double submit | `UNIQUE(siswa_id, tanggal)` + handling `QueryException` → `422 ABSENSI_DUPLICATE` |
| Edit presensi (Siswa) | Hanya jika `status_submit = 'failed'` → `ABSENSI_EDIT_BLOCKED` jika sudah `success` |
| Retry foto | `POST /api/absensi/upload-foto` (partial save) |
| Batalkan presensi | `DELETE /api/absensi/{id}` oleh admin atau siswa pemilik (hapus file + record) |
| **Tambah presensi manual (Admin/Guru)** | Berdasarkan tombol **`+ Tambah Presensi`** pada mockup `admin-presensi-siswa.png` & form `admin-presensi-siswa-form-tambah-koreksi.png`: endpoint `POST /api/absensi/manual` (role `admin,guru`) untuk mencatat presensi siswa yang **belum presensi** pada tanggal tertentu (`siswa_id`, `tanggal`, `status`, `jam_shift_mulai`, `jam_shift_selesai`, `jam_masuk` opsional, `keterangan` opsional untuk `hadir` dan wajib untuk `izin`/`sakit`/`libur`) tanpa wajib foto/GPS, mencatat `dikoreksi_oleh = auth()->id()`, `status_submit = 'success'`, dan mengirim notifikasi `absensi-corrected` ke siswa |
| **Koreksi manual (Admin/Guru)** | `POST /api/absensi/{id}/koreksi` oleh admin/guru (guru hanya siswa bimbingannya); memperbarui `status`, `keterangan` (opsional jika `hadir`, wajib jika selain `hadir`), `jam_masuk`, `jam_shift_mulai`, `jam_shift_selesai`, set `dikoreksi_oleh`, dan kirim notifikasi `absensi-corrected` |
| **Ekspor Excel presensi (Admin/Guru)** | Berdasarkan tombol **`Export Excel`** pada mockup `admin-presensi-siswa.png`: endpoint `GET /api/absensi/export` (role `admin,guru`) menghasilkan unduhan `.xlsx` (`presensi-siswa-{tanggal}.xlsx`) dengan filter `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `jurusan_id`, `gelombang_id`, `status`, `siswa_id`, `search` |
| **Filter & Data Tabel Monitoring Admin** | `GET /api/absensi` mendukung filter tambahan `search` (nama/NIS siswa) & `jurusan_id` (Program Studi), serta `AbsensiSiswaResource` memuat `siswa.kelas` dan nama `perusahaan` dari penempatan aktif siswa sesuai kolom tabel di mockup |
| Rekap | `GET /api/absensi/rekap` (agregat per siswa) + `GET /api/absensi/siswa/{id}/periode` (bulanan + rincian harian) |
| Gate onboarding & PKL aktif | Middleware `profil.confirmed` pada endpoint siswa; siswa harus punya `lokasi_pkl` active dan tanggal presensi dalam periode `praktik_mulai`..`praktik_selesai` gelombang (`GELOMBANG_NOT_ACTIVE`) |
| Integrasi dashboard & siswa bimbingan | `GET /api/dashboard/admin` dan `GET /api/guru/{id}/siswa-bimbingan` menampilkan `presensi_hari_ini` nyata |

---

## 2. Ruang Lingkup

### Termasuk

- Migration/model/seeder `absensi_siswa` (termasuk `jam_shift_mulai`, `jam_shift_selesai`, `berkas_pendukung`)
- **11 endpoint** Presensi:
  - 9 endpoint awal: list, rekap, periode, upload-foto, detail, check-in siswa, edit siswa, delete, koreksi
  - **2 endpoint tambahan hasil review mockup admin:**
    1. `POST /api/absensi/manual` — Tambah presensi manual oleh Admin/Guru
    2. `GET /api/absensi/export` — Export Excel presensi oleh Admin/Guru
- Dukungan filter `search` (nama/NIS) & `jurusan_id` pada `GET /api/absensi` + informasi `kelas` & `perusahaan` di `AbsensiSiswaResource`
- Dukungan `jam_shift_mulai` & `jam_shift_selesai` pada form koreksi (`POST /api/absensi/{absensi}/koreksi`)
- File upload foto & berkas pendukung ke `storage/app/public/uploads/presensi/`
- Anti-double submit + retry + partial save
- Koreksi & tambah manual + notifikasi database ke siswa
- Rekap agregat per siswa & rekap bulanan per siswa
- Integrasi ke `GuruController@siswaBimbingan` dan `DashboardController@admin`
- Feature test setiap endpoint + skenario error + export Excel
- Update dokumentasi + Postman

### Tidak Termasuk / Ditunda

- Validasi radius GPS (tidak divalidasi, hanya direkam) — sesuai konfirmasi
- Jam keluar / foto keluar (tidak ada)
- Jurnal harian → Sprint 5
- Dashboard guru & siswa → Sprint 7

### Ringkasan Endpoint (11 endpoint + 2 integrasi)

| # | Method & Endpoint | Role | Keterangan |
|---|---|---|---|
| 13.1 | `GET /api/absensi` | admin, guru, siswa | List (filter `siswa_id`, `jurusan_id`, `search`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `status`, `page`). Siswa hanya miliknya. |
| 13.2 | `GET /api/absensi/rekap` | admin, guru | Rekap agregat per siswa (filter `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`). |
| 13.3 | `GET /api/absensi/export` | admin, guru | **Baru (Mockup Admin):** Export daftar presensi ke file `.xlsx` sesuai filter query. |
| 13.4 | `GET /api/absensi/siswa/{siswa}/periode` | admin, guru, siswa | Rekap bulanan 1 siswa (`?bulan=`, `?tahun=`). Siswa hanya miliknya. |
| 13.5 | `POST /api/absensi/upload-foto` | siswa (`profil.confirmed`) | Retry upload foto terpisah. |
| 13.6 | `POST /api/absensi/manual` | admin, guru | **Baru (Mockup Admin):** Tambah presensi manual untuk siswa (oleh admin atau guru pembimbing). |
| 13.7 | `GET /api/absensi/{absensi}` | admin, guru, siswa | Detail absensi (siswa hanya miliknya). |
| 13.8 | `POST /api/absensi` | siswa (`profil.confirmed`) | Check-in siswa (multipart foto + GPS + jam shift + keterangan + berkas pendukung). |
| 13.9 | `PUT /api/absensi/{absensi}` | siswa (`profil.confirmed`) | Edit jika `status_submit = 'failed'`. |
| 13.10 | `DELETE /api/absensi/{absensi}` | admin, siswa | Batalkan presensi. |
| 13.11 | `POST /api/absensi/{absensi}/koreksi` | admin, guru | Koreksi manual (`status`, `jam_masuk`, `jam_shift_mulai`, `jam_shift_selesai`, `keterangan`) + notifikasi ke siswa. |
| *Integrasi* | `GET /api/guru/{guru}/siswa-bimbingan` | admin, guru | Populate `presensi_hari_ini` (status, jam_masuk). |
| *Integrasi* | `GET /api/dashboard/admin` | admin | Populate `presensi_hari_ini` (`hadir`, `izin`, `sakit`, `tidak_absen`). |

> **Urutan route penting:** `/api/absensi/rekap`, `/api/absensi/export`, `/api/absensi/siswa/{siswa}/periode`, `/api/absensi/upload-foto`, dan `/api/absensi/manual` **wajib** didaftarkan sebelum `/api/absensi/{absensi}`.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B5)

Mengikuti pola Sprint 1–3 (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/sprint-4-plan` ✅ Selesai**
- Buat dokumen rencana ini (`.agents/tasks/sprint-4-presensi-plan.md`).
- Commit: `[BE] docs: rencana Sprint 4 presensi harian`.

### **Tahap B1 — `feature/absensi-checkin` ✅ Selesai**
1. **Migration:** `create_absensi_siswa_table` (lengkap dengan `unique(['siswa_id', 'tanggal'])`).
2. **Model:** `AbsensiSiswa` + relasi `siswa()`, `korektor()` (`belongsTo(User, 'dikoreksi_oleh')`), serta relasi `Siswa::absensi()` (`hasMany`).
3. **Request & Resource:**
   - `StoreAbsensiRequest`, `UpdateAbsensiRequest`, `UploadFotoAbsensiRequest` di `app/Http/Requests/Absensi/`.
   - `AbsensiSiswaResource` (menyertakan URL foto publik via `Storage::url()`).
4. **Controller (`AbsensiSiswaController`):**
   - `index`, `show`, `store` (check-in + simpan foto + server timestamp + `next_action`), `uploadFoto`, `update` (hanya `status_submit = 'failed'`), `destroy`.
5. **Seeder:** `AbsensiSiswaSeeder` (sample presensi beberapa hari untuk siswa yang punya `lokasi_pkl` aktif).
6. **Test:** `tests/Feature/Absensi/AbsensiCheckinTest.php` (`Storage::fake('public')`).
7. **Commit:** `[BE] feat: tabel & check-in presensi harian siswa`.

### **Tahap B2 — `feature/absensi-koreksi-rekap` ✅ Selesai**
1. **Request & Notification:**
   - `KoreksiAbsensiRequest` (`status`, `keterangan`, `jam_masuk`).
   - `AbsensiCorrectedNotification` (channel `database`, type `absensi-corrected`).
2. **Controller (`AbsensiSiswaController` lanjutan):**
   - `koreksi`, `rekap`, `periodeSiswa`.
3. **Integrasi Endpoint Existing:**
   - Update `GuruController@siswaBimbingan` & `DashboardController@admin`.
4. **Test:** `tests/Feature/Absensi/AbsensiKoreksiRekapTest.php`.
5. **Commit:** `[BE] feat: koreksi presensi, rekap & integrasi dashboard`.

### **Tahap B3 — `docs/sprint-4-presensi` & Update Jam Shift/Berkas ✅ Selesai**
- Dokumentasi Sprint 4, Postman collection, serta penambahan kolom `jam_shift_mulai`, `jam_shift_selesai`, dan `berkas_pendukung` (Task 4.6–4.8).

### **Tahap B4 — `feature/absensi-admin-export` (Estimasi: 1 hari) — *Tindak Lanjut Review Mockup***
1. **Request & Export Class:**
   - `StoreManualAbsensiRequest` di `app/Http/Requests/Absensi/`:
     - `siswa_id` (`required`, `exists:siswa,id`)
     - `tanggal` (`required`, `date`)
     - `status` (`required`, `in:hadir,izin,sakit,libur`)
     - `jam_masuk` (`nullable`, `date_format:H:i,H:i:s`)
     - `jam_shift_mulai` (`nullable`, `date_format:H:i,H:i:s`)
     - `jam_shift_selesai` (`nullable`, `date_format:H:i,H:i:s`, `after:jam_shift_mulai`)
     - `keterangan` (`nullable` jika `hadir`, `required` jika `izin/sakit/libur`, `string`, `max:1000`)
   - Update `KoreksiAbsensiRequest`:
     - Tambahkan dukungan `jam_shift_mulai` & `jam_shift_selesai` (`nullable`, `date_format:H:i,H:i:s`), serta jadikan `keterangan` wajib hanya jika `status != 'hadir'` (atau `nullable` saat `hadir` sesuai form mockup).
   - `AbsensiExport` di `app/Exports/AbsensiExport.php` (menggunakan `PhpSpreadsheet` / `Maatwebsite\Excel`, konsisten dengan `TemplateController`):
     - Kolom Excel: `No`, `Tanggal`, `NIS`, `Nama Siswa`, `Kelas`, `Jurusan`, `Perusahaan PKL`, `Status`, `Waktu Kirim (Jam Masuk)`, `Jam Shift`, `Keterangan`, `Dikoreksi Oleh`.
2. **Controller (`AbsensiSiswaController`):**
   - `storeManual(StoreManualAbsensiRequest $request)`:
     - Otorisasi: `admin` bisa untuk semua siswa; `guru` hanya untuk siswa bimbingannya di `lokasi_pkl` aktif (`403` jika bukan).
     - Validasi siswa memiliki `lokasi_pkl` aktif & tanggal berada dalam rentang gelombang (`422 GELOMBANG_NOT_ACTIVE`).
     - Cek duplikat `siswa_id` + `tanggal` → `422 ABSENSI_DUPLICATE`.
     - Simpan record dengan `dikoreksi_oleh = auth()->id()`, `status_submit = 'success'`, `jam_masuk = $request->jam_masuk ?? ($status === 'hadir' ? now()->format('H:i:s') : null)`.
     - Kirim `AbsensiCorrectedNotification` ke akun user siswa.
   - Update `koreksi`: simpan `jam_shift_mulai` & `jam_shift_selesai` jika dikirim.
   - `export(Request $request)`: filter sama seperti `index` (`tanggal`, `tanggal_mulai`, `tanggal_selesai`, `jurusan_id`, `gelombang_id`, `status`, `siswa_id`, `search`), kembalikan stream download `.xlsx`.
   - Update `index`: tambahkan filter `search` (cari `siswa.nis` atau `siswa.user.name`) dan `jurusan_id` (`siswa.jurusan_id`).
3. **Resource (`AbsensiSiswaResource`):**
   - Sertakan `kelas` dan `jurusan` pada objek `siswa`, serta informasi `perusahaan` (dari `lokasiPkl` aktif) agar tabel di halaman Presensi Siswa Admin dapat langsung menampilkan kolom `SISWA (Kelas)` dan `PERUSAHAAN`.
4. **Test (`tests/Feature/Absensi/AbsensiAdminExportTest.php`):**
   - Admin dapat menambah presensi manual (`201`) & tercatat `dikoreksi_oleh` + notifikasi terkirim.
   - Guru pembimbing dapat menambah presensi manual untuk siswa bimbingannya (`201`), guru lain ditolak (`403`), siswa ditolak (`403`).
   - Duplikat presensi saat tambah manual ditolak (`422 ABSENSI_DUPLICATE`).
   - Koreksi presensi dapat memperbarui `jam_shift_mulai` & `jam_shift_selesai`.
   - Export Excel (`GET /api/absensi/export`) mengembalikan status `200` dengan `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` untuk admin/guru, dan `403` untuk siswa.
   - Filter `search` & `jurusan_id` pada `GET /api/absensi` berfungsi akurat.
5. **Commit:** `[BE] feat: tambah presensi manual & export excel presensi oleh admin`.

### **Tahap B5 — `docs/sprint-4-admin-export` (Estimasi: 0.25 hari)**
1. Update `.agents/tech-specs/2026/003-api-endpoints.md` (§13 tambah `POST /api/absensi/manual` & `GET /api/absensi/export`, update total endpoint).
2. Update `.agents/tasks/backend-roadmap.md` (centang test Task 4.6–4.7 + tambah Task 4.9 Tambah & Export Presensi Admin).
3. Update `docs/API-Docs-fondasi.md`, `.agents/tasks/postman-sprint4-execution.md`, dan `docs/postman/` (collection + environment JSON).
4. **Commit:** `[BE] docs: dokumentasi tambah manual & export excel presensi`.

---

## 5. Pola Route & Konvensi

```php
Route::middleware('auth:sanctum')->group(function () {
    // ... (route Sprint 1-3 tetap)

    // Absensi - rekap & export (admin, guru) — WAJIB sebelum {absensi}
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('absensi/rekap', [AbsensiSiswaController::class, 'rekap']);
        Route::get('absensi/export', [AbsensiSiswaController::class, 'export']);
        Route::post('absensi/manual', [AbsensiSiswaController::class, 'storeManual']);
        Route::post('absensi/{absensi}/koreksi', [AbsensiSiswaController::class, 'koreksi']);
    });

    // Absensi - siswa transaksional (gate profil) — upload-foto sebelum {absensi}
    Route::middleware(['role:siswa', 'profil.confirmed'])->group(function () {
        Route::post('absensi', [AbsensiSiswaController::class, 'store']);
        Route::post('absensi/upload-foto', [AbsensiSiswaController::class, 'uploadFoto']);
        Route::put('absensi/{absensi}', [AbsensiSiswaController::class, 'update']);
    });

    // Absensi - read (admin, guru, siswa)
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('absensi', [AbsensiSiswaController::class, 'index']);
        Route::get('absensi/siswa/{siswa}/periode', [AbsensiSiswaController::class, 'periodeSiswa']);
        Route::get('absensi/{absensi}', [AbsensiSiswaController::class, 'show']);
    });

    // Absensi - delete (admin, siswa)
    Route::middleware('role:admin,siswa')->group(function () {
        Route::delete('absensi/{absensi}', [AbsensiSiswaController::class, 'destroy']);
    });
});
```

| Aspek | Konvensi |
|---|---|
| Controller | `AbsensiSiswaController` |
| Request | `app/Http/Requests/Absensi/` (`StoreAbsensiRequest`, `StoreManualAbsensiRequest`, `UpdateAbsensiRequest`, `UploadFotoAbsensiRequest`, `KoreksiAbsensiRequest`) |
| Export | `app/Exports/AbsensiExport.php` |
| Resource | `AbsensiSiswaResource` |
| Notification | `App\Notifications\AbsensiCorrectedNotification` |
| Response | check-in `{success, message, data, next_action}` 201; manual `{message, data}` 201; list `{data, links, meta}` |
| File upload | `Storage::disk('public')`, path `uploads/presensi/{date}/{user_id}_{timestamp}_{random}.{ext}` |
| Test | `tests/Feature/Absensi/*`, `RefreshDatabase`, `Storage::fake('public')` |

---

## 6. Error Codes Sprint 4

| Code | HTTP | Kapan |
|---|---|---|
| `ABSENSI_DUPLICATE` | 422 | Sudah presensi pada tanggal tersebut (baik check-in siswa maupun tambah manual admin/guru) |
| `ABSENSI_EDIT_BLOCKED` | 422 | Siswa mencoba edit presensi yang sudah `success` |
| `GELOMBANG_NOT_ACTIVE` | 422 | Siswa belum punya lokasi PKL aktif atau presensi di luar periode praktik gelombang |
| `PROFIL_BELUM_KONFIRMASI` | 403 | Siswa belum konfirmasi profil |

---

## 7. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama + test baru Sprint 4 (termasuk B4) hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan route:list --path=api/absensi` — route statis (`rekap`, `export`, `manual`, `upload-foto`, `siswa/{siswa}/periode`) terdaftar sebelum `{absensi}`.
4. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; sample presensi tersedia.
5. `php artisan storage:link` — symlink `public/storage` aktif (untuk `Storage::url()`).

---

## 8. Definisi Selesai (DoD)

- [x] B1 Check-in merged (migration + model + seeder + controller + resource + test)
- [x] B2 Koreksi & rekap merged (koreksi + notifikasi + rekap + periode + integrasi dashboard/siswa-bimbingan + test)
- [x] B3 Dokumentasi + Postman + Jam Shift & Berkas Pendukung merged
- [ ] B4 Tambah presensi manual (`POST /api/absensi/manual`) + Export Excel (`GET /api/absensi/export`) + filter `search`/`jurusan_id` + test merged
- [ ] B5 Dokumentasi & Postman untuk endpoint tambah manual & export presensi sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
