# Execution Plan: Update Sprint 0-4 berdasarkan Dokumen Terbaru

**Branch:** `rev/catatan-uiux-fe-1`  
**Created:** 11 November 2026  
**Basis:** `.agents/tasks/backend-roadmap.md`, `.agents/tech-specs/2026/002-database-schema.md`, `.agents/tech-specs/2026/003-api-endpoints.md`, `.agents/PRD.md`  
**Referensi Gap:** `.agents/Catatan_FE_untuk_BE_Simakin.md`

---

## Ringkasan Eksekutif

Setelah menyelesaikan Phase 1 (Update Dokumentasi Sprint 5-6), ditemukan bahwa Sprints 0-4 memiliki beberapa task yang masih pending (unchecked) dan gap yang teridentifikasi dari catatan Front-End Web. Dokumen ini membuat rencana eksekusi untuk menyelesaikan dan memperbarui Sprints 0-4 agar konsisten dengan dokumen terbaru.

### Status Sprints 0-4 Saat Ini

| Sprint | Periode | Status | Task Selesai | Task Pending |
|---|---|---|---|---|
| 0 | 9-13 Okt | ✅ Selesai | 18/21 | 3 pending |
| 1 | 14-20 Okt | ✅ Selesai | 35/36 | 1 pending |
| 2 | 21-27 Okt | ✅ Selesai | 28/28 | 0 pending |
| 3 | 28 Okt - 3 Nov | ✅ Selesai | 18/18 | 0 pending |
| 4 | 4-10 Nov | ✅ Selesai | 18/18 | 0 pending |

### Gap yang Teridentifikasi dari FE (Belum Ditangani)

Dari `.agents/Catatan_FE_untuk_BE_Simakin.md`, ditemukan gap berikut yang mempengaruhi Sprints 0-4:

| # | Gap | Sprint Terkait | Status di Phase 1 |
|---|---|---|---|
| 1 | `is_active` di master tables (jurusan, kompetensi, sikap, gelombang, perusahaan, guru, users) | Sprint 1 | ❌ Belum ditangani |
| 2 | `bidang_keahlian` di jurusan | Sprint 1 | ❌ Belum ditangani |
| 3 | `kode` unique validation di jurusan & kompetensi | Sprint 1 | ❌ Belum ditangani |
| 4 | `nisn` di siswa | Sprint 2 | ❌ Belum ditangani |
| 5 | `telp`, `jurusan`, `kuota_bimbingan`, `alamat` di guru | Sprint 2 | ❌ Belum ditangani |
| 6 | `tempat_lahir`, `tanggal_lahir` di siswa | Sprint 2 | ❌ Belum ditangani |
| 7 | Catatan saat approve pengajuan (bukan hanya saat reject) | Sprint 3 | ❌ Belum ditangani |
| 8 | `lokasi_pkl`: tanggal mulai/selesai, pembimbing industri | Sprint 3 | ❌ Belum ditangani |
| 9 | `jam shift` di absensi (waktu_absen vs diisi siswa) | Sprint 4 | ❌ Belum ditangani |
| 10 | `berkas pendukung izin` di absensi | Sprint 4 | ❌ Belum ditangani |

---

## Phase A: Penyelesaian Task Pending di Sprints 0-4

### A.1 Sprint 0: Auth Lengkap + Fondasi

**Task Pending:**

| Task | Status | Action |
|---|---|---|
| 0.3: Admin reset kata sandi `PUT /api/users/{id}/password` | `[ ]` (deferred ke Sprint 2) | ✅ Sudah selesai di Sprint 2 Task 2.4. Update checklist → `[x]` |
| 0.5: Mulai Postman collection (folder 01. Authentication) | `[ ]` | ⚠️ Masih pending. Buat Postman collection folder auth |
| 0.6: Review PDF user flow + mockup | `[ ]` | ⚠️ Masih pending. Review mockup untuk konteks Sprint 5-6 |

**Action Items:**

1. **Update checklist Task 0.3:**
   - Ubah `[ ]` → `[x]` dengan catatan "Selesai di Sprint 2 Task 2.4"
   - Tambah catatan: Reset password ke NIP/NIS sudah didukung (admin input password sesuai NIP/NIS user)

2. **Postman Collection (Task 0.5):**
   - Buat folder `01. Authentication` di Postman collection
   - Endpoint: POST /api/login (web & mobile), POST /api/logout, GET /api/user, POST /api/register
   - Dokumentasi request/response sesuai docs/API-Docs-fondasi.md

3. **Review User Flow (Task 0.6):**
   - Review mockup di `docs/mockups/` untuk konteks Sprint 5-6
   - Fokus: jurnal harian (mobile), penilaian (mobile), admin jurnal (web)
   - Catat field yang perlu di API untuk konsistensi dengan UI

**Estimasi:** 0.5 hari

---

### A.2 Sprint 1: Data Master Core

**Task Pending:**

| Task | Status | Action |
|---|---|---|
| 1.7: Template Excel download endpoint | `[ ]` (opsional) | ⚠️ Masih opsional. Defer ke Sprint 7/8 atau buat sebagai file statis |

**Gap dari FE yang Memerlukan Update:**

| Gap | Field | Tabel | Action |
|---|---|---|---|
| is_active | boolean | jurusan, kompetensi, sikap, gelombang, perusahaan, waktu_absen | Tambah kolom `is_active` (default true) via ALTER TABLE |
| bidang_keahlian | varchar | jurusan | Tambah kolom `bidang_keahlian` |
| kode unique | unique constraint | jurusan, kompetensi | Tambah UNIQUE constraint pada `kode` |
| radius_presensi | int | perusahaan | 🔄 Dipindahkan ke Sprint 1 (dari Sprint 6) |
| pembimbing_industri | varchar | perusahaan | 🔄 Dipindahkan ke Sprint 1 (dari Sprint 6) |
| bidang_usaha | varchar | perusahaan | 🔄 Dipindahkan ke Sprint 1 (dari Sprint 6) |

**Action Items:**

1. **Template Excel (Task 1.7):**
   - Buat file template statis di `docs/templates/` (siswa, guru, perusahaan, kompetensi)
   - Atau tambah endpoint `GET /api/templates/{type}` (opsional, defer ke Sprint 7)

2. **Gap: `is_active` di Master Tables:**
   - **Keputusan:** Tambah kolom `is_active` (boolean, default true) ke tabel: jurusan, kompetensi, sikap, gelombang, perusahaan, waktu_absen
   - **Migration:** `ALTER TABLE ... ADD COLUMN is_active BOOLEAN DEFAULT TRUE`
   - **Impact:** Endpoint list perlu filter `?is_active=true/false`, soft delete jadi toggle aktif/nonaktif
   - **Estimasi:** 1 hari (migration + update controller + test)
   - **Prioritas:** Medium (UI punya toggle aktif/nonaktif di semua master)

3. **Gap: `bidang_keahlian` di Jurusan:**
   - **Keputusan:** Tambah kolom `bidang_keahlian` (varchar 100, nullable) ke tabel jurusan
   - **Migration:** `ALTER TABLE jurusan ADD COLUMN bidang_keahlian VARCHAR(100) NULLABLE`
   - **Impact:** Update seeder, form jurusan, API docs
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium

4. **Gap: `kode` Unique Validation:**
   - **Keputusan:** Tambah UNIQUE constraint pada kolom `kode` di jurusan dan kompetensi
   - **Migration:** `ALTER TABLE jurusan ADD UNIQUE (kode)`, `ALTER TABLE kompetensi ADD UNIQUE (kode)`
   - **Impact:** Update validation rules di Form Request
   - **Estimasi:** 0.5 hari
   - **Prioritas:** High (UI menulis "Pastikan kode unik")

5. **Gap: Field tambahan di tabel `perusahaan` (dipindahkan dari Sprint 6):**
   - **Keputusan:** Tambah kolom `radius_presensi`, `pembimbing_industri`, `bidang_usaha` di tabel perusahaan (Sprint 1, bukan Sprint 6)
   - **Migration:** `ALTER TABLE perusahaan ADD COLUMN radius_presensi INT UNSIGNED DEFAULT 100, ADD COLUMN pembimbing_industri VARCHAR(255) NULLABLE, ADD COLUMN bidang_usaha VARCHAR(100) NULLABLE`
   - **Impact:** Update form perusahaan (admin), update seeder, update API docs, update bulk upload template
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium (dibutuhkan untuk validasi penilaian di Sprint 6, tapi tersedia lebih awal)

**Total Estimasi Sprint 1 Update:** 2.5 hari (migration + controller + test)

---

### A.3 Sprint 2: User Detail - Guru & Siswa

**Status:** Semua task sudah selesai ✅

**Gap dari FE yang Memerlukan Update:**

| Gap | Field | Tabel | Action |
|---|---|---|---|
| nisn | varchar | siswa | Tambah kolom `nisn` (nullable) |
| tempat_lahir | varchar | siswa | Tambah kolom `tempat_lahir` (nullable) |
| tanggal_lahir | date | siswa | Tambah kolom `tanggal_lahir` (nullable) |
| telp | varchar | guru | Tambah kolom `telp` (nullable) |
| jurusan_id | FK | guru | Tambah kolom `jurusan_id` (nullable, FK ke jurusan) |
| kuota_bimbingan | int | guru | Tambah kolom `kuota_bimbingan` (nullable, default NULL) |
| alamat | text | guru | Tambah kolom `alamat` (nullable) |
| is_active | boolean | guru, siswa | Tambah kolom `is_active` (default true) |

**Action Items:**

1. **Gap: Field tambahan di tabel `siswa`:**
   - **Keputusan:** Tambah kolom `nisn`, `tempat_lahir`, `tanggal_lahir`
   - **Migration:** `ALTER TABLE siswa ADD COLUMN nisn VARCHAR(20) NULLABLE, ADD COLUMN tempat_lahir VARCHAR(100) NULLABLE, ADD COLUMN tanggal_lahir DATE NULLABLE`
   - **Impact:** Update onboarding form (siswa isi data diri), update seeder, update API docs
   - **Estimasi:** 1 hari
   - **Prioritas:** Medium (UI form "Lengkapi Data Diri" punya field tempat/tanggal lahir, NISN)

2. **Gap: Field tambahan di tabel `guru`:**
   - **Keputusan:** Tambah kolom `telp`, `jurusan_id`, `kuota_bimbingan`, `alamat`
   - **Migration:** `ALTER TABLE guru ADD COLUMN telp VARCHAR(20) NULLABLE, ADD COLUMN jurusan_id BIGINT UNSIGNED NULLABLE, ADD COLUMN kuota_bimbingan INT UNSIGNED NULLABLE, ADD COLUMN alamat TEXT NULLABLE`
   - **Impact:** Update form guru (admin), update seeder, update API docs, update bulk upload template
   - **Estimasi:** 1 hari
   - **Prioritas:** Medium (UI form "Tambah Guru" punya field telp, jurusan, kuota bimbingan, alamat)

3. **Gap: `is_active` di guru & siswa:**
   - **Keputusan:** Tambah kolom `is_active` (boolean, default true)
   - **Migration:** `ALTER TABLE guru ADD COLUMN is_active BOOLEAN DEFAULT TRUE`, `ALTER TABLE siswa ADD COLUMN is_active BOOLEAN DEFAULT TRUE`
   - **Impact:** Update controller (toggle aktif/nonaktif), update seeder
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium

4. **Update Bulk Upload Template:**
   - Tambah kolom di template Excel: `nisn`, `tempat_lahir`, `tanggal_lahir` (siswa); `telp`, `jurusan`, `kuota_bimbingan`, `alamat` (guru)
   - **Estimasi:** 0.5 hari

**Total Estimasi Sprint 2 Update:** 3 hari

---

### A.4 Sprint 3: Pengajuan & Penempatan PKL

**Status:** Semua task sudah selesai ✅

**Gap dari FE yang Memerlukan Update:**

| Gap | Field/Endpoint | Tabel | Action |
|---|---|---|---|
| Catatan saat approve pengajuan | `catatan` field | pengajuan_pkl | Tambah kolom `catatan` (nullable) atau gunakan `alasan_penolakan` untuk kedua aksi |
| lokasi_pkl: tanggal mulai/selesai | `tanggal_mulai`, `tanggal_selesai` | lokasi_pkl | Tambah 2 kolom date |
| lokasi_pkl: pembimbing industri | `pembimbing_industri` | lokasi_pkl | Tambah kolom varchar (atau ambil dari perusahaan) |
| Status mapping | 4 status BE vs 2 status UI | lokasi_pkl | Dokumentasi mapping di API docs |
| Bulk approve | ✅ Sudah ada | pengajuan_pkl | Tidak perlu action |

**Action Items:**

1. **Gap: Catatan saat approve pengajuan:**
   - **Keputusan:** Tambah kolom `catatan` (text, nullable) di `pengajuan_pkl` untuk menyimpan catatan saat approve (bukan hanya `alasan_penolakan` saat reject)
   - **Alternatif:** Gunakan `alasan_penolakan` untuk kedua aksi (rename ke `catatan`)
   - **Rekomendasi:** Tambah kolom baru `catatan` (nullable) agar lebih jelas
   - **Migration:** `ALTER TABLE pengajuan_pkl ADD COLUMN catatan TEXT NULLABLE`
   - **Impact:** Update endpoint PUT approve (terima `catatan`), update API docs
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium (FE mockup menunjukkan "Catatan untuk Siswa" juga dipakai saat terima)

2. **Gap: `lokasi_pkl` tanggal mulai/selesai:**
   - **Keputusan:** Tambah kolom `tanggal_mulai` dan `tanggal_selesai` (date, nullable) di `lokasi_pkl`
   - **Alternatif:** Ambil dari `gelombang.praktik_mulai` dan `gelombang.praktik_selesai`
   - **Rekomendasi:** Tambah kolom di `lokasi_pkl` untuk fleksibilitas (admin bisa override)
   - **Migration:** `ALTER TABLE lokasi_pkl ADD COLUMN tanggal_mulai DATE NULLABLE, ADD COLUMN tanggal_selesai DATE NULLABLE`
   - **Impact:** Update form penempatan, update API docs
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Low (bisa ambil dari gelombang)

3. **Gap: `lokasi_pkl` pembimbing industri:**
   - **Keputusan:** Tidak perlu kolom baru; ambil dari `perusahaan.pembimbing_industri` (sudah ditambah di Phase 1)
   - **Action:** Update API response `lokasi_pkl` untuk include `perusahaan.pembimbing_industri`
   - **Estimasi:** 0.25 hari
   - **Prioritas:** Low

4. **Gap: Status mapping dokumentasi:**
   - **Action:** Tambah dokumentasi mapping di `003-api-endpoints.md`:
     - BE status `pending` → UI: "Menunggu"
     - BE status `active` → UI: "Aktif PKL"
     - BE status `completed` → UI: "Selesai"
     - BE status `cancelled` → UI: "Dibatalkan"
   - **Estimasi:** 0.25 hari
   - **Prioritas:** Low

5. **Bulk approve:** ✅ Sudah diimplementasikan (Task 3.1). Tidak perlu action.

**Total Estimasi Sprint 3 Update:** 1.5 hari

---

### A.5 Sprint 4: Presensi Harian

**Status:** Semua task sudah selesai ✅

**Gap dari FE yang Memerlukan Update:**

| Gap | Field/Endpoint | Tabel | Action |
|---|---|---|---|
| jam shift | konsep waktu_absen vs diisi siswa | absensi_siswa / waktu_absen | Dokumentasi konsep + keputusan |
| berkas pendukung izin | file upload | absensi_siswa | Tambah kolom `berkas_pendukung` (nullable) |

**Action Items:**

1. **Gap: Konsep jam shift:**
   - **Masalah:** BE punya master `waktu_absen` (jam masuk/selesai), tapi UI menulis "Jam shift diisi siswa" (rentang bebas, mis. 07:00-15:00)
   - **Keputusan:** Pertahankan `waktu_absen` sebagai master jam kerja normal, tapi siswa bisa input jam shift saat presensi (opsional)
   - **Action:** Tambah kolom `jam_shift_mulai` dan `jam_shift_selesai` (time, nullable) di `absensi_siswa` untuk siswa input jam shift
   - **Alternatif:** Tidak tambah kolom; jam shift disimpan di `keterangan` (text)
   - **Rekomendasi:** Tambah kolom terpisah untuk query/filter yang lebih mudah
   - **Migration:** `ALTER TABLE absensi_siswa ADD COLUMN jam_shift_mulai TIME NULLABLE, ADD COLUMN jam_shift_selesai TIME NULLABLE`
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium (UI punya field jam shift)

2. **Gap: Berkas pendukung izin:**
   - **Keputusan:** Tambah kolom `berkas_pendukung` (varchar 500, nullable) di `absensi_siswa` untuk upload file (PDF/image) saat izin/sakit
   - **Migration:** `ALTER TABLE absensi_siswa ADD COLUMN berkas_pendukung VARCHAR(500) NULLABLE`
   - **Impact:** Update endpoint POST /api/absensi (terima file), update validation (max 5MB, PDF/image)
   - **Estimasi:** 0.5 hari
   - **Prioritas:** Medium (UI menunjukkan upload surat izin)

3. **Update dokumentasi konsep jam shift:**
   - Tambah catatan di `002-database-schema.md` dan `003-api-endpoints.md` tentang konsep jam shift
   - **Estimasi:** 0.25 hari

**Total Estimasi Sprint 4 Update:** 1.25 hari

---

## Phase B: Dokumentasi & Konsistensi

### B.1 Update Dokumen Tech Spec

**File yang perlu diupdate:**

1. **`.agents/tech-specs/2026/002-database-schema.md`**
   - Tambah kolom `is_active` di: jurusan, kompetensi, sikap, gelombang, perusahaan (sudah), waktu_absen, guru, siswa
   - Tambah kolom `bidang_keahlian` di jurusan
   - Tambah UNIQUE constraint `kode` di jurusan, kompetensi
   - Tambah kolom `nisn`, `tempat_lahir`, `tanggal_lahir` di siswa
   - Tambah kolom `telp`, `jurusan_id`, `kuota_bimbingan`, `alamat` di guru
   - Tambah kolom `catatan` di pengajuan_pkl
   - Tambah kolom `tanggal_mulai`, `tanggal_selesai` di lokasi_pkl
   - Tambah kolom `jam_shift_mulai`, `jam_shift_selesai`, `berkas_pendukung` di absensi_siswa
   - Update business rules & status

2. **`.agents/tech-specs/2026/003-api-endpoints.md`**
   - Tambah query param `?is_active=true/false` di list endpoints master
   - Tambah field di response guru (telp, jurusan, kuota_bimbingan, alamat)
   - Tambah field di response siswa (nisn, tempat_lahir, tanggal_lahir)
   - Tambah field `catatan` di approve pengajuan
   - Tambah field `tanggal_mulai`, `tanggal_selesai` di lokasi_pkl
   - Tambah field `jam_shift_mulai`, `jam_shift_selesai`, `berkas_pendukung` di absensi
   - Tambah dokumentasi status mapping lokasi_pkl

3. **`.agents/PRD.md`**
   - Update role matrix: tambah "Toggle aktif/nonaktif" untuk admin
   - Update scope PAD 1: tambah catatan field tambahan

4. **`.agents/tasks/backend-roadmap.md`**
   - Tambah task di Sprint 1: is_active, bidang_keahlian, kode unique
   - Tambah task di Sprint 2: field tambahan guru & siswa
   - Tambah task di Sprint 3: catatan approve, tanggal mulai/selesai
   - Tambah task di Sprint 4: jam shift, berkas pendukung izin

### B.2 Update Backend Roadmap Sprints 0-4

**Sprint 0:**
- Task 0.3: Ubah `[ ]` → `[x]` (selesai di Sprint 2)
- Task 0.5: Tambah catatan "Postman collection folder auth - pending"
- Task 0.6: Tambah catatan "Review mockup - pending"

**Sprint 1:**
- Tambah Task 1.8: `is_active` di master tables (1 hari)
- Tambah Task 1.9: `bidang_keahlian` di jurusan (0.5 hari)
- Tambah Task 1.10: `kode` unique validation (0.5 hari)
- Task 1.7: Update status (opsional → defer ke Sprint 7)

**Sprint 2:**
- Tambah Task 2.6: Field tambahan guru (telp, jurusan, kuota_bimbingan, alamat, is_active) (1 hari)
- Tambah Task 2.7: Field tambahan siswa (nisn, tempat_lahir, tanggal_lahir, is_active) (1 hari)
- Tambah Task 2.8: Update bulk upload template (0.5 hari)

**Sprint 3:**
- Tambah Task 3.5: Catatan saat approve pengajuan (0.5 hari)
- Tambah Task 3.6: Tanggal mulai/selesai di lokasi_pkl (0.5 hari)
- Tambah Task 3.7: Status mapping dokumentasi (0.25 hari)

**Sprint 4:**
- Tambah Task 4.6: Jam shift di absensi (0.5 hari)
- Tambah Task 4.7: Berkas pendukung izin (0.5 hari)
- Tambah Task 4.8: Dokumentasi konsep jam shift (0.25 hari)

---

## Ringkasan Estimasi

### Total Estimasi Update Sprints 0-4

| Sprint | Update Task | Estimasi | Prioritas |
|---|---|---|---|
| 0 | Penyelesaian pending (Postman, review mockup) | 0.5 hari | Low |
| 1 | is_active, bidang_keahlian, kode unique, field tambahan perusahaan | 2.5 hari | High |
| 2 | Field tambahan guru & siswa, update template | 2.5 hari | High |
| 3 | Catatan approve, tanggal mulai/selesai, status mapping | 1.25 hari | Medium |
| 4 | Jam shift, berkas pendukung izin, dokumentasi | 1.25 hari | Medium |
| **Total** | | **8 hari** | |

### Prioritas Eksekusi

1. **High (Sprint 1 & 2):** `is_active`, `bidang_keahlian`, `kode` unique, field guru & siswa
   - Alasan: UI punya toggle aktif/nonaktif dan field tambahan di form
   - Dampak: Perubahan schema + controller + test

2. **Medium (Sprint 3 & 4):** Catatan approve, tanggal mulai/selesai, jam shift, berkas pendukung
   - Alasan: UI punya field ini tapi bisa diisi default/dari data lain
   - Dampak: Perubahan schema + controller (minor)

3. **Low (Sprint 0):** Postman collection, review mockup
   - Alasan: Tidak mempengaruhi fungsionalitas
   - Dampak: Dokumentasi saja

---

## Phase C: Keputusan yang Diperlukan

### Decision Points untuk Tim

| # | Keputusan | Rekomendasi | Alasan |
|---|---|---|---|
| 1 | `is_active` di master tables | ✅ Tambah | UI punya toggle aktif/nonaktif |
| 2 | `bidang_keahlian` di jurusan | ✅ Tambah | UI punya field ini |
| 3 | `kode` unique validation | ✅ Tambah | UI menulis "Pastikan kode unik" |
| 4 | `nisn` di siswa | ✅ Tambah | UI menunjukkan dua angka (NIS / NISN) |
| 5 | `tempat_lahir`, `tanggal_lahir` di siswa | ✅ Tambah | UI form "Lengkapi Data Diri" |
| 6 | `telp`, `jurusan`, `kuota_bimbingan`, `alamat` di guru | ✅ Tambah | UI form "Tambah Guru" |
| 7 | `catatan` di pengajuan_pkl | ✅ Tambah (atau rename `alasan_penolakan` → `catatan`) | FE note #10 |
| 8 | `tanggal_mulai`, `tanggal_selesai` di lokasi_pkl | ⚠️ Opsional (bisa ambil dari gelombang) | FE note #11 |
| 9 | `jam_shift_mulai`, `jam_shift_selesai` di absensi | ⚠️ Opsional (bisa simpan di keterangan) | FE note #22 |
| 10 | `berkas_pendukung` di absensi | ✅ Tambah | FE note #22 (UI menunjukkan upload surat izin) |
| 11 | Konsep jam shift: master waktu_absen vs diisi siswa | ⚠️ Diskusi dengan tim | FE note #7 |

---

## Phase D: Timeline Eksekusi

### Opsi 1: Eksekusi Paralel dengan Sprint 5 (Rekomendasi)

| Hari | Aktivitas | Sprint |
|---|---|---|
| 11 Nov | Review & konfirmasi decision points | - |
| 12 Nov | Sprint 1 update: is_active, bidang_keahlian, kode unique, field tambahan perusahaan (radius_presensi, pembimbing_industri, bidang_usaha) | Sprint 1 |
| 13 Nov | Sprint 2 update: field guru & siswa | Sprint 2 |
| 14 Nov | Sprint 3 update: catatan approve, tanggal mulai/selesai | Sprint 3 |
| 15 Nov | Sprint 4 update: jam shift, berkas pendukung | Sprint 4 |
| 16 Nov | Dokumentasi & test | - |
| 17 Nov | Buffer | - |

**Total:** 5 hari (paralel dengan Sprint 5 yang sudah direvisi jadi 4 hari)

### Opsi 2: Eksekusi Setelah Sprint 6

Jika tim memutuskan untuk fokus Sprint 5-6 dulu, update Sprints 0-4 bisa dilakukan setelah Sprint 6 selesai (sekitar 18-24 Nov).

---

## Phase E: Checklist Progress

### Sprint 0
- [x] Task 0.3: Update checklist (selesai di Sprint 2) — ✅ Done
- [x] Task 0.5: Buat Postman collection folder auth — ✅ Sudah ada di docs/postman/
- [x] Task 0.6: Review mockup untuk konteks Sprint 5-6 — ✅ Done (lihat .agents/Mockup_Review_Sprint_5-6.md)

### Sprint 1
- [ ] Task 1.7: Template Excel (defer atau buat statis)
- [x] Task 1.8: `is_active` di master tables (migration ✅, model ✅, form request ✅, resource ✅, controller filter ⏳, test ⏳)
- [x] Task 1.9: `bidang_keahlian` di jurusan (migration ✅, model ✅, form request ✅, resource ✅, seeder ⏳, test ⏳)
- [x] Task 1.10: `kode` unique validation (migration ✅, form request ✅, test ⏳)
- [x] Task 1.11: Field tambahan perusahaan: `radius_presensi`, `pembimbing_industri`, `bidang_usaha` (migration ✅, model ✅, form request ✅, resource ✅, seeder ⏳, test ⏳)

### Sprint 2
- [x] Task 2.6: Field tambahan guru (telp, jurusan_id, kuota_bimbingan, alamat, is_active) — migration ✅, model ✅, form request ✅, resource ✅, controller ✅, test ⏳
- [x] Task 2.7: Field tambahan siswa (nisn, tempat_lahir, tanggal_lahir, is_active) — migration ✅, model ✅, form request ✅, resource ✅, controller ✅, test ⏳
- [x] Task 2.8: Update bulk upload template Excel — Import class updated ✅, file template Excel statis perlu edit manual ⏳

### Sprint 3
- [x] Task 3.5: Catatan saat approve pengajuan — migration ✅, model ✅, form request ✅, controller ✅, resource ✅, test ⏳
- [x] Task 3.6: Tanggal mulai/selesai di lokasi_pkl — migration ✅, model ✅, form request ✅, resource ✅, test ⏳
- [x] Task 3.7: Status mapping dokumentasi — ✅ done in 003-api-endpoints.md

### Sprint 4
- [ ] Task 4.6: Jam shift di absensi
- [ ] Task 4.7: Berkas pendukung izin
- [ ] Task 4.8: Dokumentasi konsep jam shift

### Dokumentasi
- [ ] Update `002-database-schema.md` (semua field baru)
- [ ] Update `003-api-endpoints.md` (semua endpoint update)
- [ ] Update `PRD.md` (role matrix, scope)
- [ ] Update `backend-roadmap.md` (tambah task baru)

---

## Catatan Penting

1. **Semua perubahan schema menggunakan ALTER TABLE** (bukan recreate migration) karena Sprints 0-4 sudah selesai dan data mungkin sudah ada.

2. **Backward compatibility:** Field baru harus nullable dengan default value agar tidak break existing data.

3. **Testing:** Setiap perubahan schema perlu di-test dengan data existing (migration up & down).

4. **Dokumentasi:** Semua perubahan harus didokumentasikan di `002-database-schema.md` dan `003-api-endpoints.md` sebelum implementasi.

5. **Prioritas:** Fokus pada High priority items dulu (is_active, bidang_keahlian, kode unique, field guru & siswa). Medium/Low bisa di-defer ke Sprint 7-8.