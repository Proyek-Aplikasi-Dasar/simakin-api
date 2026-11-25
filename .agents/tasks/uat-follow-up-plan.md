# Rencana Tindak Lanjut — Catatan Analisis UAT (UI/UX → BE)

**Status:** Rencana disetujui, siap eksekusi
**Tanggal catatan:** UAT (review Nov 2026)
**Sumber:** `.agents/catatan_analisis_uat_untuk_backend_dari_uiux.md`
**Prasyarat:** Sprint 0–6 selesai & merged ke `develop`; modul absensi, jurnal, pengajuan PKL, lokasi PKL, user management, bulk upload sudah berjalan
**Goal:** Menutup temuan UAT dari UI/UX: bug impor Excel (jika masih ada), password default import, validasi kuota saat approve, pembatasan akses presensi guru, serta pengecekan asumsi (filter bidang usaha, 404 pengajuan, default verifikasi).

---

## 1. Ringkasan Catatan & Pemetaan ke Tugas

### A. Perlu Diperbaiki (Confirmed)

| # | Catatan UAT | Dampak BE | Prioritas |
|---|---|---|---|
| UAT-1 | Import Excel selalu ditolak (Siswa, Guru, Perusahaan, Kompetensi, Akun). Controller harus simpan file temp dengan ekstensi `.xlsx` sebelum dikirim ke importer. | Pastikan `bulkUpload` di semua controller menyimpan file ke path dengan ekstensi asli (atau meneruskan `getClientOriginalExtension()` ke `ImportsRows::import()`). Bug ini sudah diperbaiki di Tahap B4 follow-up (cek ekstensi dari `getClientOriginalExtension()`), namun perlu verifikasi end-to-end via UAT. | **Kritis** |
| UAT-2 | Sandi akun hasil import masih `password123`, harus diganti jadi NIS (sekaligus menutup item "Sandi awal siswa nggak otomatis NIS"). | `GuruImport::saveRow()` → `password = nip` (bukan `'password123'`). `SiswaImport::saveRow()` → `password = nis` (bukan `'password123'`). Update dokumentasi (API-Docs, Postman) yang menyebut password default import. | Tinggi |
| UAT-3 | Kuota tidak dicek saat approve (`PengajuanPklController@approve`). Tambah validasi: kalau kuota perusahaan sudah penuh, tolak approve. | Tambah pengecekan kuota di `approve()` (dan `bulkApprove()`) sebelum membuat `lokasi_pkl`: hitung `terpakai` (status `pending`/`active` pada perusahaan & gelombang yang sama); jika `perusahaan.kuota !== null && terpakai >= kuota` → `422 PERUSAHAAN_KUOTA_PENUH`. Juga tangani `SISWA_ALREADY_PLACED` dengan respons `422` (bukan `RuntimeException` → 500). | Tinggi |
| UAT-4 | Guru bisa lihat presensi siswa non-bimbingan (`AbsensiSiswaController`). Filter query berdasarkan relasi guru pembimbing. | `AbsensiSiswaController::index()` → jika user adalah guru, batasi `siswa_id` hanya siswa yang ada di `lokasi_pkl` dengan `guru_id = user->guru->id` dan `status = 'active'`. Guru tidak boleh melihat presensi siswa lain. | Tinggi |

### B. Perlu Dicek (Asumsi)

| # | Catatan UAT | Dampak BE | Rekomendasi |
|---|---|---|---|
| UAT-5 | Filter bidang usaha perusahaan: kalau mau ditambah, endpoint list perusahaan harus terima parameter `bidang_usaha` (dan kolom/relasinya harus ada di data). | `PerusahaanController::index()` → tambah filter `bidang_usaha` (query `where('bidang_usaha', 'like', "%...%")` atau exact). Kolom `bidang_usaha` sudah ada di `perusahaan` (migration `add_fields_to_master_tables`). | Opsional (tergantung kebutuhan FE) |
| UAT-6 | ID pengajuan tidak ada: backend sebaiknya balikin `404` dengan pesan jelas, bukan error umum. Cek respons sekarang. | Route model binding `PengajuanPkl` sudah mengembalikan `404` default Laravel (JSON `{"message":"No query results for model [App\\Models\\PengajuanPkl] ..."}`) — pesan kurang jelas. Tambahkan handler di `bootstrap/app.php` (render `NotFoundHttpException`) atau override di controller (`find()` manual + `abort(404, 'Pengajuan tidak ditemukan.')`). | Sedang |
| UAT-7 | Default verifikasi "Diterima": sumber masalah item Canceled. Kalau mau bisa dites, default-nya dihapus dan keputusan jadi wajib diisi (validasi required). | Cari field dengan default verifikasi "Diterima" (kemungkinan di `penilaian`/`aktivasi_penilaian` — Sprint 6, belum dibangun) atau di `pengajuan_pkl` (status default sudah `pending`). Karena modul penilaian (Sprint 6) belum dibangun, item ini **ditunda** ke Sprint 6 (Task 6.2 Aktivasi Periode). Jika merujuk ke status default pengajuan, ubah dari `pending`? **Tidak disarankan** — biarkan `pending` dan wajibkan keputusan approve/reject. | Rendah (Sprint 6) |

---

## 2. Ruang Lingkup (Termasuk / Tidak Termasuk)

### Termasuk

- Perbaikan password default import: `GuruImport` & `SiswaImport` (password = NIP/NIS), bukan `'password123'`.
- Tambah validasi kuota di `PengajuanPklController::approve()` & `bulkApprove()` (+ tangani `SISWA_ALREADY_PLACED` sebagai 422, bukan 500).
- Filter presensi di `AbsensiSiswaController::index()` berdasarkan relasi guru pembimbing.
- Filter `bidang_usaha` di `PerusahaanController::index()` (opsional, tergantung FE).
- Penanganan `404` dengan pesan jelas untuk ID pengajuan yang tidak ada.
- Update dokumentasi & Postman (password default import, filter baru).

### Tidak Termasuk / Ditunda

- Item UAT-7 (default verifikasi "Diterima") → Sprint 6 (Task 6.2), karena modul penilaian belum dibangun.
- Dashboard guru & siswa → Sprint 7.
- Rekap Excel → Sprint 7.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B4)

Mengikuti pola Sprint 1–6 (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/uat-follow-up`**
- Buat dokumen rencana ini (`.agents/tasks/uat-follow-up-plan.md`).
- Commit: `[BE] docs: rencana tindak lanjut analisis UAT (UI/UX)`.

### **Tahap B1 — `fix/impor-password-default` (Estimasi: 0.5 hari)**
1. **Import:** `GuruImport::saveRow()` → `password = nip` (bukan `'password123'`); `SiswaImport::saveRow()` → `password = nis` (bukan `'password123'`).
2. **Dokumentasi:** update catatan password default di `API-Docs-fondasi.md` (seksi import) dan playbook Postman (jika menyebut `password123` sebagai default import).
3. **Test:** update test impor (assert password = NIP/NIS setelah import) — tambah test jika belum ada.
4. **Commit:** `[BE] fix: password default hasil import = NIP/NIS (bukan password123)`.

### **Tahap B2 — `fix/pengajuan-kuota-approve` (Estimasi: 0.5 hari)**
1. **Controller `PengajuanPklController::approve()`:** tambah pengecekan kuota perusahaan (hitung `terpakai` pada `lokasi_pkl` dengan `status in ('pending','active')` pada perusahaan & gelombang yang sama); jika `perusahaan.kuota !== null && terpakai >= kuota` → `422 PERUSAHAAN_KUOTA_PENUH`.
2. **Tangani `SISWA_ALREADY_PLACED`:** ganti `throw new \RuntimeException('SISWA_ALREADY_PLACED')` dengan respons `422` + `code: SISWA_ALREADY_PLACED` (transaksi rollback).
3. **`bulkApprove()`:** terapkan pengecekan kuota & placed per ID; ID yang gagal dilaporkan di `failed[]` (tidak menggagalkan seluruh transaksi).
4. **Test:** tambah skenario approve saat kuota penuh → `422 PERUSAHAAN_KUOTA_PENUH`; approve saat siswa sudah placed → `422 SISWA_ALREADY_PLACED`.
5. **Commit:** `[BE] fix: validasi kuota & placed saat approve pengajuan PKL`.

### **Tahap B3 — `fix/absensi-guru-filter` (Estimasi: 0.5 hari)**
1. **Controller `AbsensiSiswaController::index()`:** jika user adalah guru, batasi query ke `siswa_id` yang ada di `lokasi_pkl` dengan `guru_id = user->guru->id` dan `status = 'active'`. Guru tidak boleh melihat presensi siswa lain.
2. **Controller `AbsensiSiswaController::rekap()` & `periodeSiswa()`:** terapkan filter yang sama untuk guru (hanya data siswa bimbingannya).
3. **Test:** tambah skenario guru pembimbing hanya melihat presensi siswa bimbingannya; guru lain → 403/empty.
4. **Commit:** `[BE] fix: filter presensi siswa berdasarkan relasi guru pembimbing`.

### **Tahap B4 — `feature/opsional-bidang-usaha-404-pengajuan` (Estimasi: 0.5 hari)**
1. **Filter `bidang_usaha` (opsional):** `PerusahaanController::index()` → tambah query `bidang_usaha` (exact atau `like`).
2. **404 pengajuan jelas:** `PengajuanPklController::show()` → gunakan `findOrFail` dengan pesan `Pengajuan tidak ditemukan.` (custom exception handler atau `abort(404, ...)`).
3. **Test:** assert 404 dengan pesan jelas saat ID tidak ada; assert filter `bidang_usaha` berfungsi.
4. **Commit:** `[BE] feat: filter bidang usaha perusahaan & 404 jelas untuk pengajuan`.

### **Tahap B5 — `docs/uat-follow-up` (Estimasi: 0.25 hari)**
1. Update `.agents/tech-specs/2026/003-api-endpoints.md` (password default import, kuota approve, filter guru, 404 pengajuan).
2. Update `docs/API-Docs-fondasi.md` (bagian Akun, Guru, Pengajuan PKL, Presensi, Perusahaan).
3. Update `.agents/tasks/postman-sprint*-execution.md` jika diperlukan.
4. **Commit:** `[BE] docs: sinkronisasi tindak lanjut UAT`.

---

## 4. Pola Route & Konvensi (Tambahan)

| Aspek | Konvensi |
|---|---|
| Password default import | `password = nip` (guru) / `password = nis` (siswa) — konsisten dengan `UserImport` (`password = nipNis`) |
| Kuota perusahaan | `perusahaan.kuota` (nullable; `null` = tanpa batas). Terpakai = `lokasi_pkl` dengan status `in ('pending','active')` pada perusahaan & gelombang yang sama. |
| Akses presensi guru | Guru hanya boleh melihat presensi siswa yang ada di `lokasi_pkl` dengan `guru_id = user->guru->id` dan `status = 'active'`. |
| 404 pengajuan | Pesan: `"Pengajuan tidak ditemukan."` (bukan pesan default Laravel). |

---

## 5. Error Codes (Reuse)

| Code | HTTP | Kapan |
|---|---|---|
| `PERUSAHAAN_KUOTA_PENUH` | 422 | Kuota perusahaan sudah penuh (saat pengajuan **dan** saat approve) |
| `SISWA_ALREADY_PLACED` | 422 | Siswa sudah punya `lokasi_pkl` active |
| `PENGAJUAN_DUPLICATE` | 422 | Siswa sudah punya pengajuan pending di gelombang tsb |

---

## 6. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama + test baru hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan.
4. Uji impor Excel end-to-end (upload `.xlsx` via multipart) → **berhasil** (tidak lagi `INVALID_FILE_TYPE`).
5. Uji login dengan akun hasil import (password = NIP/NIS) → **berhasil**.
6. Uji approve saat kuota penuh → `422 PERUSAHAAN_KUOTA_PENUH`.
7. Uji guru melihat presensi siswa non-bimbingan → ditolak / tidak tampil.
8. Uji `GET /api/pengajuan-pkl/{id}` dengan ID tidak ada → `404` dengan pesan jelas.

---

## 7. Definisi Selesai (DoD)

- [x] B0 Rencana tindak lanjut UAT dibuat (dokumen ini)
- [x] B1 Password default import = NIP/NIS (import + test)
- [x] B2 Validasi kuota & placed saat approve (approve + bulkApprove + test)
- [x] B3 Filter presensi siswa berdasarkan relasi guru pembimbing (index + rekap + periode + test)
- [x] B4 Filter bidang usaha perusahaan + 404 jelas untuk pengajuan (opsional + test)
- [x] B5 Dokumentasi + Postman sinkron
- [x] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih

---

Apakah rencana tindak lanjut UAT ini sudah sesuai? Jika sudah setuju, silakan beralih dari **Plan mode** ke **Build mode** agar kita bisa mulai dari **Tahap B0 (`docs/uat-follow-up`)**.