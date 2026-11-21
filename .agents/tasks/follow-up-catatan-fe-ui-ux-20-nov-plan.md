# Rencana Tindak Lanjut — Catatan FE UI/UX & BE (20 Nov 2026)

**Status:** Rencana disetujui, siap eksekusi
**Tanggal catatan:** 20 November 2026
**Sumber:** `.agents/catatan_dari_fe_ui_ux_dan_be_untuk_be_20-nov.md`
**Prasyarat:** Sprint 0–5 selesai & merged ke `develop` (absensi, jurnal, pengajuan PKL, lokasi PKL, dashboard admin, user management, template Excel)
**Goal:** Menutup gap UI/UX FE (data yang belum tersedia di BE), memperbaiki bug impor Excel, serta menyesuaikan validasi & zona waktu sesuai catatan FE dan BE.

---

## 1. Ringkasan Catatan & Pemetaan ke Tugas

| # | Sumber | Catatan | Dampak BE | Prioritas |
|---|---|---|---|---|
| FE-1 | Admin — Akun & Peran | Kolom "Login terakhir" (`"Hari ini, 07:12"`) & "Status" (`"Aktif"`/`"Nonaktif"`) belum ada | `UserResource` + kolom `last_login_at` (nullable) di `users`; soft delete sudah merepresentasikan nonaktif | Tinggi |
| FE-2 | Admin — Guru Pembimbing | Email di detail guru ada di UI tetapi tidak ada di BE | Tambah `email` ke `guru`? atau pakai `users.email` (sudah ada); pastikan `GuruResource` mengirim `email` dari relasi `user.email` | Sedang |
| FE-3 | Admin — Pengajuan PKL | Field `"Dipilih Dari"` (`"Daftar perusahaan mitra"` / `"Daftar perusahaan siswa"`) & `"Tanggal Pengajuan"` belum tersedia | Tambah kolom `sumber_pilihan` (`enum('daftar_perusahaan_mitra','daftar_perusahaan_siswa')` nullable) & pastikan `created_at` (tanggal pengajuan) dikirim di `PengajuanPklResource` | Tinggi |
| FE-4 | Admin — Lokasi & Penempatan | (a) Pembimbing Industri & Koordinat belum ada di endpoint penempatan (hanya tampil dari data perusahaan). Dibutuhkan untuk form tambah penempatan.<br>(b) Jika penempatan PKL diisi **manual oleh admin** (`POST /api/lokasi-pkl`), statusnya harus langsung **`active`** (saat ini BE memberi status `pending`). | Tambah field `pembimbing_industri` (string nullable), `latitude` (decimal), `longitude` (decimal) ke `lokasi_pkl` (atau ambil dari `perusahaan`); update `StoreLokasiPklRequest`, `UpdateLokasiPklRequest`, `LokasiPklResource`. **Ubah default status `POST /api/lokasi-pkl` (manual admin) dari `pending` menjadi `active`** (sesuai mockup admin penempatan). | Sedang |
| FE-5 | Admin — Presensi Siswa | Kurang informasi waktu kirim presensi & jam shift | Pastikan `jam_masuk`, `jam_shift_mulai`, `jam_shift_selesai` (sudah ada sejak Sprint 4 update) terkirim di `AbsensiSiswaResource` (sudah) + dokumentasi | Rendah |
| FE-6 | Admin — Impor Excel | Semua `bulk-upload` ditolak `422 INVALID_FILE_TYPE` karena cek ekstensi dari path sementara (`phpXXXX.tmp`) | Perbaiki `ImportsRows::readRows()` agar tidak cek ekstensi dari path sementara; gunakan `getClientOriginalExtension()` atau tentukan reader secara eksplisit (`\Maatwebsite\Excel\Excel::XLSX`) | **Kritis** |
| UI/UX-1 | UI/UX | Fitur token untuk penilaian dihapus; cukup admin membuka akses penilaian pada tanggal 1–10 tiap bulan | Hapus field/token verifikasi di aktivasi penilaian (Sprint 6 nanti); pastikan `aktivasi_penilaian` hanya buka/tutup periode 1–10 | Rendah (Sprint 6) |
| BE-1 | BE | Validasi email, nomor telepon, dan seluruh field untuk input/edit data | Tambah `email` (format+unique where applicable), `telp`/`telp_ortu` (regex numeric, max length) di semua Form Request (`StoreSiswaRequest`, `UpdateSiswaRequest`, `StoreGuruRequest`, `UpdateGuruRequest`, `StorePerusahaanRequest`, `UpdatePerusahaanRequest`, `StoreUserRequest`/`UpdateUserRequest`, `StoreManualAbsensiRequest`) | Tinggi |
| BE-2 | BE | Waktu kirim presensi masih berformat GMT, perlu WIB (GMT+7) | Pastikan `jam_masuk` & `created_at` dikirim dalam zona waktu `Asia/Jakarta` (config `app.timezone` atau konversi eksplisit di resource) | Tinggi |

---

## 2. Ruang Lingkup (Termasuk / Tidak Termasuk)

### Termasuk

- Penambahan kolom `last_login_at` pada `users` (nullable) + pencatatan saat login (mobile & web) + pengiriman di `UserResource`.
- Penambahan kolom `sumber_pilihan` (`enum('daftar_perusahaan_mitra','daftar_perusahaan_siswa')` nullable) pada `pengajuan_pkl` + kirim di `PengajuanPklResource`.
- Penambahan kolom `pembimbing_industri`, `latitude`, `longitude` (nullable) pada `lokasi_pkl` + validasi + resource.
- Penyesuaian status default `POST /api/lokasi-pkl` (manual admin) menjadi `active` (sebelumnya `pending`).
- Perbaikan bug impor Excel di `app/Imports/Concerns/ImportsRows.php` (cek ekstensi dari `getClientOriginalExtension()` / tentukan reader eksplisit).
- Validasi email & nomor telepon di seluruh Form Request input/edit.
- Penyesuaian zona waktu presensi ke WIB (GMT+7).
- Update `GuruResource` agar mengirim `email` dari relasi `user.email`.
- Update dokumentasi (`003-api-endpoints.md`, `API-Docs-fondasi.md`) + playbook Postman jika diperlukan.

### Tidak Termasuk / Ditunda

- Penghapusan field/token verifikasi penilaian → Sprint 6 (Task 6.1–6.3).
- Dashboard guru & siswa → Sprint 7.
- Rekap Excel → Sprint 7.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B4)

Mengikuti pola Sprint 1–5 (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/follow-up-catatan-fe-20nov`**
- Buat dokumen rencana ini (`.agents/tasks/follow-up-catatan-fe-ui-ux-20-nov-plan.md`).
- Commit: `[BE] docs: rencana tindak lanjut catatan FE UI/UX & BE`.

### **Tahap B1 — `feature/akun-login-terakhir-status` (Estimasi: 0.5 hari)**
1. **Migration:** `add_last_login_at_to_users_table` (`last_login_at` timestamp nullable).
2. **Model:** `User` → tambah `last_login_at` ke `$fillable`/`$casts` (datetime) + helper `isAdmin()`/`isGuru()`/`isSiswa()` sudah ada.
3. **Controller:** `LoginController::login` → set `$user->last_login_at = now()` (zona WIB) saat login mobile & web; simpan.
4. **Resource:** `UserResource` → kirim `last_login_at` (format `Y-m-d H:i:s` / null) dan `status` (`'Aktif'` jika `deleted_at === null` else `'Nonaktif'`).
5. **Test:** `tests/Feature/Auth/LoginTest.php` (update) — assert `last_login_at` terisi setelah login; assert `status` pada `GET /api/user`.
6. **Commit:** `[BE] feat: kolom login terakhir & status akun`.

### **Tahap B2 — `feature/guru-email-pembimbing-industri-koordinat` (Estimasi: 1 hari)**
1. **Migration:** `add_email_to_guru_table`? Atau manfaatkan `users.email` yang sudah ada (disarankan: pakai `users.email` agar konsisten dengan onboarding siswa). Keputusan: **pakai `users.email`**; pastikan `GuruResource` mengirim `email` dari relasi `user.email`. Jika diperlukan field terpisah, tambahkan kolom `email` di `guru` (nullable) — **disarankan pakai `users.email`** untuk konsistensi.
2. **Migration:** `add_pembimbing_industri_koordinat_to_lokasi_pkl_table` (`pembimbing_industri` varchar(255) nullable, `latitude` decimal(10,8) nullable, `longitude` decimal(11,8) nullable). *Catatan:* jika nilai hanya tampil dari data perusahaan, field boleh nullable dan default mengambil dari `perusahaan` (fallback di resource).
3. **Request:** `StoreLokasiPklRequest` & `UpdateLokasiPklRequest` → tambah `pembimbing_industri`, `latitude`, `longitude` (nullable, validasi lat/long numeric & range).
4. **Resource:** `LokasiPklResource` → kirim `pembimbing_industri`, `latitude`, `longitude` (fallback ke `perusahaan.latitude/longitude` & `perusahaan.pimpinan` jika null).
5. **Controller:** `LokasiPklController@store` → ubah default `status` dari `pending` menjadi **`active`** untuk penempatan yang diisi manual oleh admin (sesuai mockup `admin-presensi-siswa-form-tambah-koreksi.png` / catatan FE 20 Nov). Guard `SISWA_ALREADY_PLACED` tetap berlaku (siswa yang sudah punya lokasi `active` tidak boleh dibuat ulang).
6. **GuruResource:** tambah `email` (dari `user.email`).
7. **Test:** update `GuruTest`, `LokasiPklTest` (assert field baru terkirim & tersimpan; assert status `active` saat create manual).
8. **Commit:** `[BE] feat: email guru & pembimbing industri + koordinat penempatan`.

### **Tahap B3 — `feature/pengajuan-sumber-pilihan` (Estimasi: 0.5 hari)**
1. **Migration:** `add_sumber_pilihan_to_pengajuan_pkl_table` (`sumber_pilihan` enum `['daftar_perusahaan_mitra','daftar_perusahaan_siswa']` nullable, default `null`).
2. **Model:** `PengajuanPkl` → tambah `sumber_pilihan` ke `$fillable`.
3. **Request:** `StorePengajuanPklRequest` → `sumber_pilihan` nullable, `in:daftar_perusahaan_mitra,daftar_perusahaan_siswa`.
4. **Resource:** `PengajuanPklResource` → kirim `sumber_pilihan` dan pastikan `created_at` (tanggal pengajuan) terkirim dengan format `Y-m-d H:i:s`.
5. **Test:** update `PengajuanPklTest` (assert `sumber_pilihan` & `created_at` terkirim).
6. **Commit:** `[BE] feat: field "Dipilih Dari" & tanggal pengajuan`.

### **Tahap B4 — `fix/impor-excel-bug` (Estimasi: 0.5 hari)**
1. **Perbaikan `app/Imports/Concerns/ImportsRows.php`:**
   - Hapus cek ekstensi berdasarkan `pathinfo($path, PATHINFO_EXTENSION)` (path sementara `phpXXXX.tmp`).
   - Tentukan tipe reader secara eksplisit: `Excel::toArray($this, $path, null, \Maatwebsite\Excel\Excel::XLSX)` **atau** cek ekstensi dari `$request->file('file')->getClientOriginalExtension()` lalu kirim tipe readernya ke import.
   - Karena `ImportsRows` dipakai bersama, perbaikan ini berlaku untuk import Siswa, Guru, Perusahaan, Kompetensi, dan Akun.
2. **Controller:** `bulkUpload` memanggil `$request->file('file')->getRealPath()` tetap boleh, tetapi import harus menentukan reader berdasarkan `getClientOriginalExtension()` (fallback `xlsx`).
3. **Test:** tambah/aktifkan test impor Excel end-to-end (upload file `.xlsx` via `multipart`) di `ExcelImportTest` / test bulk upload yang ada; pastikan tidak lagi `INVALID_FILE_TYPE`.
4. **Commit:** `[BE] fix: impor Excel tidak lagi ditolak karena ekstensi file sementara`.

### **Tahap B5 — `feature/validasi-field-zona-waktu` (Estimasi: 1 hari)**
1. **Validasi email & nomor telepon:**
   - `StoreSiswaRequest`/`UpdateSiswaRequest`: `email` nullable email + unique; `telp`/`telp_ortu` nullable, regex `/^[0-9+\-\s]{8,20}$/`, max 20.
   - `StoreGuruRequest`/`UpdateGuruRequest`: `email` (jika ditambahkan) format+unique; `nip` tetap.
   - `StorePerusahaanRequest`/`UpdatePerusahaanRequest`: `email` nullable email; `telp` nullable max 20; `kuota` integer min 0; `latitude`/`longitude` numeric range.
   - `StoreUserRequest`/`UpdateUserRequest` (`User` management): `email` nullable email unique (ignore current); `username` required unique (ignore current); `name` required.
   - `StoreManualAbsensiRequest`: validasi `siswa_id`, `tanggal`, `status`, `jam_masuk`, `jam_shift_mulai`, `jam_shift_selesai`, `keterangan` (wajib jika `!= hadir`).
   - Tambahkan pesan validasi yang ramah (Indonesia) di semua Form Request.
2. **Zona waktu WIB (GMT+7):**
   - Pastikan `config/app.php` → `'timezone' => 'Asia/Jakarta'` (atau set di `.env` `APP_TIMEZONE=Asia/Jakarta`).
   - `AbsensiSiswaResource` → kirim `jam_masuk` & `created_at` dalam zona `Asia/Jakarta` (gunakan `$this->jam_masuk` sudah time; untuk timestamp gunakan `->timezone('Asia/Jakarta')` atau set global timezone).
   - `GuruResource` / `UserResource` → `last_login_at` dalam WIB.
   - `PengajuanPklResource` → `created_at` (tanggal pengajuan) dalam WIB.
   - (Opsional) Tambahkan accessor `created_at_wib` jika frontend membutuhkan.
3. **Test:** update test yang terdampak validasi (email/telp) dan assert format waktu WIB.
4. **Commit:** `[BE] feat: validasi field input/edit + zona waktu WIB pada presensi`.

### **Tahap B6 — `docs/follow-up-catatan-fe-20nov` (Estimasi: 0.5 hari)**
1. Update `.agents/tech-specs/2026/003-api-endpoints.md` (§2, §9, §10, §11, §12, §13: tambah field `last_login_at`, `status`, `email` guru, `sumber_pilihan`, `pembimbing_industri`, `latitude`, `longitude`, filter `search`/`jurusan_id`).
2. Update `.agents/tasks/backend-roadmap.md` (centang item validasi & tambah task follow-up catatan FE 20 Nov).
3. Update `docs/API-Docs-fondasi.md` (bagian Akun & Peran, Guru, Pengajuan PKL, Lokasi PKL, Presensi).
4. Update `.agents/tasks/postman-sprint*-execution.md` jika diperlukan (tambah field baru di body/response).
5. **Commit:** `[BE] docs: sinkronisasi catatan FE UI/UX & BE (20 Nov)`.

---

## 4. Pola Route & Konvensi (Tambahan)

| Aspek | Konvensi |
|---|---|
| Kolom baru `users.last_login_at` | `timestamp` nullable, diisi saat login (mobile & web) |
| Kolom baru `pengajuan_pkl.sumber_pilihan` | `enum('daftar_perusahaan_mitra','daftar_perusahaan_siswa')` nullable |
| Kolom baru `lokasi_pkl.pembimbing_industri` | `varchar(255)` nullable (fallback ke `perusahaan.pimpinan`) |
| Kolom baru `lokasi_pkl.latitude/longitude` | `decimal(10,8)` / `decimal(11,8)` nullable (fallback ke `perusahaan.latitude/longitude`) |
| Zona waktu | `Asia/Jakarta` (WIB, GMT+7) di `config/app.php` & `.env` |
| Validasi email | `nullable\|email:rfc,dns` atau `email` + `unique:users,email` (ignore current) |
| Validasi telp | `nullable\|string\|max:20\|regex:/^[0-9+\-\s()]+$/` |
| Impor Excel | Tentukan reader berdasarkan `getClientOriginalExtension()` (fallback `xlsx`), jangan dari `pathinfo($path)` |

---

## 5. Error Codes (Tambahan / Reuse)

| Code | HTTP | Kapan |
|---|---|---|
| `INVALID_FILE_TYPE` | 422 | File bukan `.xlsx`/`.xls`/`.csv` (setelah perbaikan, hanya jika ekstensi asli tidak valid) |
| `ABSENSI_DUPLICATE` | 422 | Presensi ganda pada tanggal yang sama (termasuk tambah manual admin) |
| `GELOMBANG_NOT_ACTIVE` | 422 | Tambah manual presensi di luar periode praktik |

---

## 6. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama + test baru hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; kolom baru terisi.
4. Upload file `.xlsx` via `multipart` ke `POST /api/siswa/bulk-upload` (dan endpoint bulk lain) → **berhasil** (tidak lagi `INVALID_FILE_TYPE`).
5. Cek `GET /api/user` mengembalikan `last_login_at` & `status`.
6. Cek `GET /api/guru/{id}` mengembalikan `email`.
7. Cek `GET /api/pengajuan-pkl/{id}` mengembalikan `sumber_pilihan` & `created_at` (WIB).
8. Cek `GET /api/lokasi-pkl/{id}` mengembalikan `pembimbing_industri`, `latitude`, `longitude`.
9. Cek `GET /api/absensi` mengembalikan `jam_masuk` & `created_at` dalam WIB.

---

## 7. Definisi Selesai (DoD)

- [ ] B0 Rencana follow-up dibuat (dokumen ini)
- [ ] B1 Kolom login terakhir & status akun (migration + model + controller + resource + test)
- [ ] B2 Email guru + pembimbing industri + koordinat + status default `active` untuk penempatan manual (migration + request + resource + controller + test)
- [ ] B3 Field "Dipilih Dari" & tanggal pengajuan (migration + model + request + resource + test)
- [ ] B4 Bug impor Excel diperbaiki (cek ekstensi dari `getClientOriginalExtension()`; test upload `.xlsx` end-to-end)
- [ ] B5 Validasi email/telp + zona waktu WIB (Form Request + resource + test)
- [ ] B6 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih

---

Apakah rencana tindak lanjut ini sudah sesuai? Jika sudah setuju, silakan beralih dari **Plan mode** ke **Build mode** agar kita bisa mulai dari **Tahap B0 (`docs/follow-up-catatan-fe-20nov`)**.