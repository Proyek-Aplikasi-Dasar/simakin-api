# Rencana Tindak Lanjut — Catatan FE Web 26 Nov 2026

**Status:** Rencana disetujui, siap eksekusi
**Tanggal catatan:** 26 November 2026
**Sumber:** `.agents/catatan_dari_fe_26-nov.md`
**Prasyarat:** Sprint 0–6 selesai & merged ke `develop`; modul master data, presensi, jurnal, pengajuan PKL, lokasi PKL, user management, bulk upload & export (jurusan, gelombang, absensi) sudah berjalan
**Goal:** (1) Memastikan **semua fitur data** (12 modul) memiliki mekanisme **ekspor Excel**; (2) Memperbaiki logika `is_active` pada `gelombang` agar **hanya boleh 1 gelombang aktif** (auto-deactivate yang lain saat save, rapikan data lama, opsional unique index + validasi import).

---

## 1. Ringkasan Catatan & Pemetaan ke Tugas

### A. Mekanisme Ekspor (FE Web)

| # | Modul | Status Saat Ini | Dampak BE |
|---|---|---|---|
| EX-1 | Siswa | ❌ Belum ada export | Tambah `GET /api/siswa/export` + `SiswaExport` |
| EX-2 | Guru | ❌ Belum ada export | Tambah `GET /api/guru/export` + `GuruExport` |
| EX-3 | Perusahaan | ❌ Belum ada export | Tambah `GET /api/perusahaan/export` + `PerusahaanExport` |
| EX-4 | Jurusan | ✅ Sudah ada (`GET /api/jurusan/export`) | — |
| EX-5 | Kompetensi | ❌ Belum ada export | Tambah `GET /api/kompetensi/export` + `KompetensiExport` |
| EX-6 | Gelombang PKL | ✅ Sudah ada (`GET /api/gelombang/export`) | — |
| EX-7 | Sikap | ❌ Belum ada export | Tambah `GET /api/sikap/export` + `SikapExport` |
| EX-8 | Lokasi & Penempatan PKL | ❌ Belum ada export | Tambah `GET /api/lokasi-pkl/export` + `LokasiPklExport` |
| EX-9 | Pengajuan PKL | ❌ Belum ada export | Tambah `GET /api/pengajuan-pkl/export` + `PengajuanPklExport` |
| EX-10 | Presensi Siswa | ✅ Sudah ada (`GET /api/absensi/export`) | — |
| EX-11 | Jurnal Harian | ❌ Belum ada export | Tambah `GET /api/jurnal/export` + `JurnalHarianExport` |
| EX-12 | Rekap Presensi & Jurnal | ⚠️ Endpoint rekap JSON sudah ada (`/absensi/rekap`, `/jurnal/rekap`); export Excel rekap belum | Tambah `GET /api/rekap/excel/presensi` & `GET /api/rekap/excel/jurnal` (Sprint 7) atau endpoint export di modul masing-masing |

### B. Validasi Gelombang Aktif (FE Web)

| # | Catatan UAT | Dampak BE | Prioritas |
|---|---|---|---|
| GL-1 | Sekarang gelombang bisa aktif banyak sekaligus, padahal harusnya cuma boleh 1. Taruh di model `Gelombang` pakai event `saving`, jadi store, update, sama import ikut kena semua. | `Gelombang` model: tambah `booted()` dengan `saving` event → jika `is_active = true`, set `is_active = false` pada semua gelombang lain. | **Tinggi** |
| GL-2 | Data gelombang yang sudah ada kayaknya banyak yang `is_active = true`. Rapikan sekali jalan (migration/seeder), sisain 1 aja yang aktif, misalnya yang periode prakteknya lagi jalan. | Buat migration `fix_multiple_active_gelombang` (atau command one-off) yang menonaktifkan semua gelombang kecuali satu (prioritas: gelombang dengan `praktik_mulai <= today <= praktik_selesai`; jika tidak ada, ambil yang terbaru). | **Tinggi** |
| GL-3 (opsional) | Bikin unique index biar nggak bisa ada 2 gelombang aktif walau dua admin save barengan, dan bikin import nolak file yang isinya lebih dari 1 gelombang aktif. | Migration: tambah partial unique index pada `gelombang(is_active)` where `is_active = true` (MySQL 8.0.13+ mendukung functional index; alternatif: unique index pada kolom virtual). `GelombangImport`: validasi agar tidak ada >1 baris dengan `is_active = true` (atau abaikan field `is_active` di import). | Sedang (opsional) |

---

## 2. Ruang Lingkup (Termasuk / Tidak Termasuk)

### Termasuk

- Penambahan export Excel (`.xlsx`) untuk modul: Siswa, Guru, Perusahaan, Kompetensi, Sikap, Lokasi PKL, Pengajuan PKL, Jurnal Harian (dan Rekap bila diperlukan).
- Penambahan endpoint `GET /api/{resource}/export` (role `admin,guru`) dengan filter yang relevan (query params per modul).
- Penambahan class `*Export` di `app/Exports/` (mengikuti pola `AbsensiExport`, `JurusanExport`, `GelombangExport`).
- Enforce "hanya 1 gelombang aktif" via model event `saving` pada `Gelombang`.
- Migration one-off untuk merapikan data gelombang lama (sisain 1 aktif).
- (Opsional) Partial unique index + validasi import `GelombangImport`.

### Tidak Termasuk / Ditunda

- Rekap Excel lintas modul (Sprint 7 — `GET /api/rekap/excel/*`).
- Dashboard guru & siswa → Sprint 7.
- Perubahan skema selain `gelombang`.

---

## 3. Strategi Branching & Tahap Pengerjaan (B0–B3)

Mengikuti pola Sprint sebelumnya (branch dari `develop`, merge via PR ke `develop` setiap tahap):

### **Tahap B0 — `docs/uat-26nov-follow-up`**
- Buat dokumen rencana ini (`.agents/tasks/uat-26nov-follow-up-plan.md`).
- Commit: `[BE] docs: rencana tindak lanjut catatan FE 26 Nov (ekspor + gelombang aktif)`.

### **Tahap B1 — `feature/gelombang-single-active` (Estimasi: 0.5 hari)**
1. **Migration:** `fix_multiple_active_gelombang` — nonaktifkan semua gelombang kecuali satu (prioritas: yang `praktik_mulai <= today <= praktik_selesai`; fallback: gelombang terbaru).
2. **Model `Gelombang`:** tambah `booted()` dengan event `saving`:
   ```php
   protected static function booted(): void
   {
       static::saving(function (Gelombang $gelombang) {
           if ($gelombang->is_active) {
               static::where('id', '!=', $gelombang->id)->update(['is_active' => false]);
           }
       });
   }
   ```
   (Store, update, import semuanya kena.)
3. **(Opsional) Migration:** partial unique index pada `gelombang(is_active)` where `is_active = true` (atau unique index pada kolom virtual `active_flag`).
4. **(Opsional) `GelombangImport`:** tolak file yang berisi >1 baris dengan `is_active = true` (atau abaikan field `is_active` di import).
5. **Test:** `tests/Feature/Master/GelombangTest.php` — assert hanya 1 gelombang aktif setelah aktivasi gelombang baru; assert gelombang lama nonaktif; assert constraint (jika unique index diterapkan).
6. **Commit:** `[BE] feat: enforce single active gelombang (auto-deactivate + cleanup data lama)`.

### **Tahap B2 — `feature/export-master-data` (Estimasi: 2 hari)**
1. **Export classes** di `app/Exports/`:
   - `SiswaExport` (kolom: No, NIS, Nama, Kelas, Jurusan, Gelombang, Jenis Kelamin, Telp, Email, Alamat, Nama Ortu, Telp Ortu, Alamat Ortu, Status Profil)
   - `GuruExport` (No, NIP, Nama, Email, Jurusan, Kuota Bimbingan, Telp, Alamat, Status Aktif)
   - `PerusahaanExport` (No, Nama, Alamat, Latitude, Longitude, Pimpinan, Telp, Email, Deskripsi, Kuota, Bidang Usaha, Jurusan, Status Aktif)
   - `KompetensiExport` (No, Jurusan, Nama, Kode, Status Aktif)
   - `SikapExport` (No, Nama, Deskripsi, Status Aktif)
   - `LokasiPklExport` (No, Siswa, NIS, Guru Pembimbing, Perusahaan, Gelombang, Status, Pembimbing Industri, Latitude, Longitude, Catatan)
   - `PengajuanPklExport` (No, Siswa, NIS, Perusahaan, Gelombang, Status, Sumber Pilihan, Tanggal Pengajuan, Alasan Penolakan, Diverifikasi Oleh, Tanggal Verifikasi)
   - `JurnalHarianExport` (No, Tanggal, Siswa, NIS, Kompetensi, Deskripsi, Foto, Edited By, Edited At)
2. **Controller:** tambah method `export(Request $request)` di `SiswaController`, `GuruController`, `PerusahaanController`, `KompetensiController`, `SikapController`, `LokasiPklController`, `PengajuanPklController`, `JurnalHarianController` (role `admin,guru`; filter relevan per modul).
3. **Route:** daftarkan `GET /api/{resource}/export` (admin only atau admin,guru) sebelum route `{id}` (gunakan `whereNumber` pada wildcard yang relevan).
4. **Template:** (opsional) tambah template Excel untuk modul yang belum punya (`sikap`, `lokasi-pkl`, `pengajuan-pkl`, `jurnal`) di `public/templates/` + daftarkan di `TemplateController::TEMPLATES`.
5. **Test:** `tests/Feature/Export/ExportMasterDataTest.php` (upload tidak diperlukan; assert export 200 + content-type xlsx untuk admin/guru; 403 untuk siswa).
6. **Commit:** `[BE] feat: ekspor Excel untuk siswa, guru, perusahaan, kompetensi, sikap, lokasi PKL, pengajuan PKL, jurnal`.

### **Tahap B3 — `docs/uat-26nov-follow-up` (Estimasi: 0.5 hari)**
1. Update `.agents/tech-specs/2026/003-api-endpoints.md` (tambah endpoint export di §3, §4, §5, §6, §7, §9, §10, §11, §12, §14; total endpoint 88 → 96+).
2. Update `.agents/tasks/backend-roadmap.md` (centang Task 4.x yang relevan + tambah task follow-up UAT 26 Nov).
3. Update `docs/API-Docs-fondasi.md` (bagian Data Master, Pengajuan PKL, Lokasi PKL, Presensi, Jurnal).
4. Update `.agents/tasks/postman-sprint*-execution.md` & `docs/postman/` (collection + environment JSON) jika diperlukan.
5. **Commit:** `[BE] docs: sinkronisasi ekspor Excel & single active gelombang`.

---

## 4. Pola Route & Konvensi (Tambahan)

| Aspek | Konvensi |
|---|---|
| Export Excel | Gunakan `Maatwebsite\Excel\Facades\Excel::download()` dengan class `*Export` (implement `FromCollection`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`) — konsisten dengan `AbsensiExport`, `JurusanExport`, `GelombangExport`. |
| Penamaan file | `{resource}-{YYYY-MM-DD}.xlsx` (contoh: `siswa-2026-11-26.xlsx`). |
| Kolom "No" | Gunakan counter private `int $row = 0` dan `++$this->row` di `map()`. |
| Gelombang aktif tunggal | Event `saving` pada model `Gelombang`; unique index opsional (partial) untuk race condition. |
| Filter export | Ikuti query params modul terkait (`gelombang_id`, `jurusan_id`, `status`, `tanggal_mulai`, `tanggal_selesai`, `search`, `siswa_id`, `guru_id`, `perusahaan_id`). |

---

## 5. Error Codes (Reuse)

| Code | HTTP | Kapan |
|---|---|---|
| `INVALID_FILE_TYPE` | 422 | File bukan `.xlsx`/`.xls`/`.csv` (setelah perbaikan) |
| `PERUSAHAAN_KUOTA_PENUH` | 422 | Kuota perusahaan sudah penuh |
| `SISWA_ALREADY_PLACED` | 422 | Siswa sudah punya `lokasi_pkl` active |

---

## 6. Verifikasi (per branch)

1. `php artisan test` — seluruh test lama + test baru hijau.
2. `./vendor/bin/pint --test` — bersih sesuai PSR-12.
3. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; hanya 1 gelombang aktif.
4. Uji `GET /api/{resource}/export` untuk semua modul yang baru (siswa, guru, perusahaan, kompetensi, sikap, lokasi-pkl, pengajuan-pkl, jurnal) → status `200` + `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
5. Uji aktivasi gelombang baru → gelombang lama otomatis `is_active = false`.
6. Uji 2 admin save barengan (jika unique index diterapkan) → salah satu gagal dengan constraint violation yang ditangani ramah.

---

## 7. Definisi Selesai (DoD)

- [ ] B0 Rencana tindak lanjut UAT 26 Nov dibuat (dokumen ini)
- [ ] B1 Single active gelombang (event `saving` + cleanup data lama + (opsional) unique index + import validation + test)
- [ ] B2 Ekspor Excel untuk 8 modul (siswa, guru, perusahaan, kompetensi, sikap, lokasi PKL, pengajuan PKL, jurnal) + template + route + test
- [ ] B3 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih

---

Apakah rencana tindak lanjut ini sudah sesuai? Jika sudah setuju, silakan beralih dari **Plan mode** ke **Build mode** agar kita bisa mulai dari **Tahap B0 (`docs/uat-26nov-follow-up`)**.