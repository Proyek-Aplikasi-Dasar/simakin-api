# Analisis Catatan UI/UX dan Front-End: Tindak Lanjut Sprint 4-5

**Tanggal:** 5 November 2026  
**Status Terakhir:** Sprint 4 (Presensi Harian) selesai  
**Sprint Berikutnya:** Sprint 5 (Jurnal Harian) dan Sprint 6 (Penilaian Bulanan)  
**Branch Kerja:** `rev/catatan-uiux-fe-1`

---

## Ringkasan Eksekutif

Setelah menyelesaikan Sprint 4 (Presensi Harian), tim UI/UX dan Front-End Web memberikan catatan penting yang perlu ditindaklanjuti sebelum melanjutkan ke Sprint 5 (Jurnal Harian). Dokumen ini menganalisis catatan tersebut dan membuat rencana eksekusi untuk update backend API, database schema, dan dokumentasi.

### Catatan dari UI/UX

1. **Endpoint bulk approve pengajuan**
2. **Penilaian: simpan waktu, koordinat, jarak, dan flag radius; siswa baca nilai saja**
3. **Hapus approval jurnal, admin boleh edit**
4. **Reset sandi ke NIP/NIS**

### Catatan dari Front-End Web

Tersimpan di `.agents/Catatan_FE_untuk_BE_Simakin.md`, berisi 16 poin ketidaksesuaian antara BE dengan UI, dan beberapa gap field yang belum ada di BE.

---

## A. Analisis Catatan UI/UX

### 1. Endpoint Bulk Approve Pengajuan ✅ SUDAH ADA

**Status:** Sudah diimplementasikan di Sprint 3

**Endpoint:** `PUT /api/pengajuan-pkl/bulk-approve`

**Lokasi Implementasi:**
- Controller: `app/Http/Controllers/PengajuanPklController.php` method `bulkApprove`
- Route: `routes/api.php`
- Dokumentasi: `.agents/tech-specs/2026/003-api-endpoints.md` baris 241

**Request Body:**
```json
{
  "pengajuan_ids": [1, 2, 3],
  "guru_pembimbing_id": 5
}
```

**Mockup Terkait:** `docs/mockups/web/admin/pkl/pengajuan-pkl/admin-pengajuan-pkl-verifikasi.png`

**Action:** ✅ Tidak perlu action, endpoint sudah tersedia.

---

### 2. Penilaian: Simpan Waktu, Koordinat, Jarak, dan Flag Radius ⚠️ PERLU UPDATE

**Konteks:** 
- UI/UX ingin sistem menyimpan metadata lengkap saat penilaian diverifikasi siswa
- Siswa hanya bisa **membaca** nilai (tidak bisa input), hanya verifikasi dengan foto + GPS
- Perlu flag `dalam_radius` untuk mendeteksi apakah verifikasi dilakukan di lokasi PKL

**Status Saat Ini:**
Tabel `penilaian_bulanan` di dokumentasi (`.agents/tech-specs/2026/002-database-schema.md` line 490+) memiliki:
- `foto_verifikasi` ✅
- `verifikasi_lat` ✅
- `verifikasi_long` ✅
- `verified_at` ✅
- ❌ **BELUM ADA:** `verifikasi_waktu`, `jarak_dari_pkl`, `dalam_radius`

**Gap yang Perlu Ditambahkan:**

| Field Baru | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `verifikasi_waktu` | timestamp | YES | NULL | Waktu saat siswa upload foto verifikasi (bisa beda dari `verified_at` jika ada delay) |
| `jarak_dari_pkl` | decimal(8,2) | YES | NULL | Jarak dalam meter dari koordinat perusahaan PKL (dihitung server-side) |
| `dalam_radius` | boolean | YES | NULL | Flag apakah verifikasi dilakukan dalam radius perusahaan (dari `perusahaan.radius_presensi`) |

**Mockup Terkait:** `docs/mockups/mobile/penilaian-bulanan/siswa-verifikasi-penilaian-bulanan.png`

**Catatan dari Mockup:**
- Siswa memasukkan **token 6 digit** yang dikirim ke pembimbing industri
- Siswa upload **foto verifikasi** dengan GPS
- Sistem menampilkan nama perusahaan dan nama lengkap siswa di screen verifikasi

**Action:**
1. Update migration `create_penilaian_bulanan_table` (tambah 3 kolom baru)
2. Update model `PenilaianBulanan` (fillable + casts)
3. Update endpoint `POST /api/penilaian/{id}/verifikasi`:
   - Hitung `jarak_dari_pkl` menggunakan Haversine formula
   - Set `dalam_radius` berdasarkan `perusahaan.radius_presensi`
   - Simpan `verifikasi_waktu` dari EXIF foto atau timestamp upload
4. Update response agar FE bisa menampilkan status "Dalam Radius" atau "Di Luar Radius"
5. Update dokumentasi API

---

### 3. Hapus Approval Jurnal, Admin Boleh Edit ⚠️ PERLU PERUBAHAN LOGIC

**Konteks:**
- UI/UX memutuskan **tidak ada approval jurnal** oleh guru
- Admin bisa mengedit jurnal siswa kapan saja (tidak peduli status)
- Siswa bisa edit jurnal sendiri tanpa batasan approval

**Status Saat Ini:**
- Dokumentasi Sprint 5 (`.agents/tasks/backend-roadmap.md` line 336-379) mencakup approval flow:
  - `PUT /api/jurnal/{id}/approve` (guru)
  - `PUT /api/jurnal/{id}/revise` (guru)
  - Field `status_review` (pending/approved/need_revision)
  - Field `is_editable` (tergantung approval)

**Mockup Terkait:**
- `docs/mockups/mobile/presensi-dan-jurnal/siswa-jurnal-harian.png`
- `docs/mockups/mobile/presensi-dan-jurnal/siswa-perbaiki-jurnal-harian.png`
- `docs/mockups/web/admin/pengguna/siswa-pkl/admin-riwayat-jurnal-siswa-new.png`

**Analisis Mockup:**
- Mobile: Siswa input jurnal dengan form bebas, tidak ada indikator status review
- Web Admin: Menampilkan riwayat jurnal dengan tombol **Edit** (bukan approve/reject)

**Perubahan yang Dibutuhkan:**

#### A. Database Schema (`jurnal_harian`)
**Hapus field:**
- `status_review` (enum: pending/approved/need_revision) → **HAPUS**
- `catatan_guru` → **HAPUS**
- `reviewed_by` → **HAPUS**
- `reviewed_at` → **HAPUS**
- `is_editable` → **HAPUS**

**Tambah field:**
- `edited_by` (bigint unsigned, nullable, FK ke `users.id`) → untuk tracking siapa yang terakhir edit
- `edited_at` (timestamp, nullable) → untuk tracking kapan terakhir diedit

#### B. API Endpoints
**Hapus endpoint:**
- `PUT /api/jurnal/{id}/approve` → **HAPUS**
- `PUT /api/jurnal/{id}/revise` → **HAPUS**

**Update endpoint:**
- `PUT /api/jurnal/{id}` → Siswa bisa edit milik sendiri kapan saja; Admin bisa edit semua jurnal
- `GET /api/jurnal/rekap` → Hapus breakdown `approved/pending/need_revision`, ganti jadi total jurnal saja

**Update notifikasi:**
- Hapus `JurnalReviewedNotification` (tidak ada review)

#### C. Business Logic
- Siswa: bisa edit/delete jurnal milik sendiri kapan saja
- Admin: bisa edit/delete semua jurnal
- Guru: bisa view jurnal siswa bimbingan (read-only)

**Action:**
1. Revisi dokumentasi Sprint 5 di `backend-roadmap.md`
2. Revisi database schema di `002-database-schema.md`
3. Revisi API endpoints di `003-api-endpoints.md`
4. Buat migration untuk tabel `jurnal_harian` dengan struktur baru
5. Update seeder jurnal (hapus status review)
6. Implementasi endpoint CRUD jurnal dengan logic baru
7. Update dashboard guru (hapus "jurnal perlu review")

---

### 4. Reset Sandi ke NIP/NIS ✅ SUDAH ADA

**Status:** Sudah diimplementasikan di Sprint 2

**Endpoint:** `PUT /api/users/{id}/password` (admin only)

**Lokasi Implementasi:**
- Controller: `app/Http/Controllers/UserController.php` method `resetPassword`
- Route: `routes/api.php`
- Dokumentasi: `.agents/tech-specs/2026/003-api-endpoints.md` dan `backend-roadmap.md` line 208

**Request Body:**
```json
{
  "password": "20210041",
  "password_confirmation": "20210041"
}
```

**Business Logic:**
- Admin reset password user ke nilai tertentu (bisa NIP/NIS)
- Setelah reset, semua token mobile dan session web user tersebut dicabut (revoke)
- User login dengan password baru

**Mockup Terkait:** `docs/mockups/web/admin/pengguna/akun-peran/` (form edit akun)

**Action:** ✅ Tidak perlu action, fitur sudah tersedia. Dokumentasi perlu diperjelas bahwa admin bisa set password ke NIP/NIS.

---

## B. Analisis Catatan Front-End Web

Dari file `.agents/Catatan_FE_untuk_BE_Simakin.md`, berikut analisis untuk Sprint 5-6:

### Gap Kritis untuk Sprint 5 (Jurnal Harian)

#### 1. Tabel `jurnal_harian` Belum Ada ❌

**Status:** Tabel belum dibuat (migration belum ada)

**Action:** Prioritas tinggi untuk Sprint 5 - buat migration, model, seeder sesuai revisi di bagian A.3 di atas.

---

### Gap Kritis untuk Sprint 6 (Penilaian Bulanan)

#### 2. Kolom Tambahan di `perusahaan` ⚠️

**Gap dari FE:**
- `radius_presensi` (integer, meter) → **PENTING** untuk validasi penilaian `dalam_radius`
- `pembimbing_industri` (string) → nama pembimbing dari pihak industri
- `bidang_usaha` (string) → sektor bisnis perusahaan

**Status Saat Ini:** Tabel `perusahaan` tidak punya field ini (lihat `002-database-schema.md`)

**Action:**
1. Tambah migration untuk alter table `perusahaan`:
   - `radius_presensi` INT UNSIGNED DEFAULT 100 (default 100 meter)
   - `pembimbing_industri` VARCHAR(255) NULLABLE
   - `bidang_usaha` VARCHAR(100) NULLABLE
2. Update seeder perusahaan
3. Update form perusahaan di dokumentasi API

---

#### 3. Tabel `penilaian_bulanan` dan Detail Belum Ada ❌

**Status:** Migration belum dibuat

**Dependensi:**
- `aktivasi_penilaian` (tabel untuk admin buka/tutup periode)
- `penilaian_bulanan` (master per siswa per bulan)
- `penilaian_kompetensi` (detail nilai kompetensi)
- `penilaian_sikap` (detail nilai sikap/soft skill)

**Action:** Prioritas tinggi untuk Sprint 6 - buat 4 migration dengan revisi dari bagian A.2 di atas.

---

#### 4. Token 6 Digit untuk Verifikasi Penilaian 🆕

**Konteks:** Mockup menunjukkan siswa memasukkan "Token Akses" 6 digit saat verifikasi penilaian.

**Analisis:**
- Token dikirim ke pembimbing industri (via WhatsApp/Email, di luar scope PAD 1)
- Siswa input token untuk konfirmasi bahwa pembimbing industri sudah input nilai
- Token bersifat one-time use per periode penilaian

**Gap:**
- Tabel `penilaian_bulanan` belum punya field `token_verifikasi`
- Belum ada endpoint untuk generate/validasi token

**Action:**
1. Tambah field di `penilaian_bulanan`:
   - `token_verifikasi` VARCHAR(6) NULLABLE UNIQUE
   - `token_generated_at` TIMESTAMP NULLABLE
   - `token_used_at` TIMESTAMP NULLABLE
2. Endpoint `POST /api/penilaian/{id}/generate-token` (guru/admin) → generate random 6 digit
3. Update endpoint `POST /api/penilaian/{id}/verifikasi` → validasi token sebelum accept foto
4. Token expire setelah 24 jam atau setelah digunakan

---

### Gap untuk Dashboard & Monitoring

#### 5. Dashboard Guru: Jurnal Tidak Perlu Review ✅

**Sesuai revisi A.3:** Dashboard guru tidak perlu tampilkan "jurnal perlu review"

**Update:** `GET /api/dashboard/guru/{id}` response hapus field `jurnal_perlu_review`

---

## C. Kesimpulan: Priority Action Items

### Immediate (Sebelum Sprint 5 dimulai)

| # | Action | Estimasi | Status |
|---|--------|----------|--------|
| 1 | Revisi dokumentasi Sprint 5 (hapus approval jurnal) | 0.5 hari | 🔴 To Do |
| 2 | Update database schema jurnal_harian | 0.5 hari | 🔴 To Do |
| 3 | Update API endpoints jurnal_harian | 0.5 hari | 🔴 To Do |
| 4 | Alter table perusahaan (tambah radius_presensi, pembimbing_industri, bidang_usaha) | 0.5 hari | 🔴 To Do |

**Total Immediate:** 2 hari

### Sprint 5 (Jurnal Harian)

| # | Action | Estimasi | Status |
|---|--------|----------|--------|
| 5 | Migration jurnal_harian (struktur revisi) | 0.5 hari | 🔴 To Do |
| 6 | Model + Seeder jurnal_harian | 0.5 hari | 🔴 To Do |
| 7 | CRUD jurnal (tanpa approval) | 1.5 hari | 🔴 To Do |
| 8 | Endpoint riwayat & rekap jurnal | 1 hari | 🔴 To Do |
| 9 | Test + dokumentasi API | 0.5 hari | 🔴 To Do |

**Total Sprint 5:** 4 hari (buffer 1 hari dari roadmap asli 5 hari)

### Sprint 6 (Penilaian Bulanan)

| # | Action | Estimasi | Status |
|---|--------|----------|--------|
| 10 | Update database schema penilaian_bulanan (tambah 3 field metadata GPS + token) | 0.5 hari | 🔴 To Do |
| 11 | Migration 4 tabel penilaian (aktivasi, bulanan, kompetensi, sikap) | 1 hari | 🔴 To Do |
| 12 | Model + relasi + seeder penilaian | 0.5 hari | 🔴 To Do |
| 13 | CRUD aktivasi penilaian (admin) | 0.5 hari | 🔴 To Do |
| 14 | Input penilaian (guru) dengan notifikasi | 1 hari | 🔴 To Do |
| 15 | Verifikasi penilaian (siswa) dengan token + GPS + jarak + radius | 1.5 hari | 🔴 To Do |
| 16 | Endpoint nilai akhir & rekap | 0.5 hari | 🔴 To Do |
| 17 | Test + dokumentasi API | 0.5 hari | 🔴 To Do |

**Total Sprint 6:** 6 hari (melebihi window 5 hari, perlu extend 1 hari atau defer test ke Sprint 8)

---

## D. Risk & Mitigation

| Risk | Impact | Probability | Mitigation |
|---|---|---|---|
| Perubahan requirement jurnal (hapus approval) mempengaruhi dashboard guru | Medium | Low | Dashboard guru sudah didesain modular, cukup hapus 1 metric |
| Token verifikasi penilaian menambah kompleksitas | Medium | Medium | Token opsional di PAD 1, bisa dibypass jika pembimbing industri tidak dapat dihubungi |
| Sprint 6 melebihi estimasi 5 hari | High | Medium | Defer comprehensive testing ke Sprint 8, fokus happy path di Sprint 6 |
| Field `radius_presensi` di perusahaan belum diisi untuk data lama | Low | High | Set default 100 meter di migration, admin update manual via UI |
| Perhitungan jarak GPS (Haversine) tidak akurat untuk lokasi indoor | Low | Low | Simpan jarak sebagai informasi saja, tidak memblokir verifikasi |

---

## E. Update Mockup yang Sudah Dikerjakan UI/UX

Berdasarkan review folder `docs/mockups/`, UI/UX sudah update mockup untuk:

### Pengajuan PKL
- `admin-pengajuan-pkl-verifikasi.png` ✅ → Sudah menunjukkan form bulk approve
- Button "Terima pengajuan" dan dropdown "Pilih guru pembimbing"

### Penilaian Bulanan
- `siswa-verifikasi-penilaian-bulanan.png` ✅ → Sudah menunjukkan:
  - Input token 6 digit
  - Upload foto verifikasi
  - Info periode penilaian

### Jurnal Harian
- `siswa-jurnal-harian.png` ✅ → Formulir input jurnal (tidak ada status review)
- `siswa-perbaiki-jurnal-harian.png` ✅ → Edit jurnal (tidak ada approval)
- `admin-riwayat-jurnal-siswa-new.png` ✅ → Admin bisa lihat dan edit jurnal

**Kesimpulan:** Mockup sudah sesuai dengan requirement revisi. Backend perlu menyesuaikan.

---

## F. Perubahan Dokumentasi yang Diperlukan

### File yang Perlu Diupdate

1. **`.agents/tasks/backend-roadmap.md`**
   - Sprint 5: Revisi Task 5.3 (hapus approval jurnal)
   - Sprint 6: Tambah catatan metadata GPS penilaian + token verifikasi

2. **`.agents/tech-specs/2026/002-database-schema.md`**
   - Tabel `perusahaan`: tambah 3 field (radius_presensi, pembimbing_industri, bidang_usaha)
   - Tabel `jurnal_harian`: revisi struktur (hapus approval fields, tambah edited_by/edited_at)
   - Tabel `penilaian_bulanan`: tambah 4 field (verifikasi_waktu, jarak_dari_pkl, dalam_radius, token_verifikasi, token_generated_at, token_used_at)

3. **`.agents/tech-specs/2026/003-api-endpoints.md`**
   - Section 14 (Jurnal Harian): hapus endpoint approve/revise, update deskripsi CRUD
   - Section 15 (Penilaian): tambah endpoint generate-token, update endpoint verifikasi

4. **`docs/API-Docs-fondasi.md`** (atau buat file baru untuk Sprint 5-6)
   - Dokumentasi lengkap endpoint jurnal & penilaian

5. **`.agents/PRD.md`**
   - Update Scope PAD 1: klarifikasi "jurnal harian tidak ada approval"
   - Update Pain Point #11: ubah deskripsi anti-kecurangan penilaian (tambah token + GPS)

---

## G. Next Steps

### Langkah 1: Review & Approval (0.5 hari)
- Diskusi dengan PM, UI/UX, dan Front-End untuk konfirmasi perubahan
- Prioritaskan: **hapus approval jurnal** vs **keep approval** (keputusan bisnis)
- Konfirmasi requirement token 6 digit untuk verifikasi penilaian

### Langkah 2: Update Dokumentasi (1 hari)
- Update 5 file dokumentasi di atas
- Commit ke branch `rev/catatan-uiux-fe-1`
- Tag file dengan `[REV] Catatan UI/UX & FE - Sprint 4-5`

### Langkah 3: Database Migration (1 hari)
- Buat migration alter table `perusahaan` (3 field)
- Buat migration create table `jurnal_harian` (struktur revisi)
- Jalankan migration di environment development
- Update seeder

### Langkah 4: Implementasi Sprint 5 (4 hari)
- Mulai development sesuai roadmap yang sudah direvisi

### Langkah 5: Implementasi Sprint 6 (6 hari)
- Termasuk fitur token verifikasi dan metadata GPS lengkap

---

## H. Decision Log

| Tanggal | Keputusan | Alasan | Oleh |
|---|---|---|---|
| 2026-11-05 | Hapus approval jurnal harian | Menyederhanakan workflow; admin langsung edit jika ada kesalahan | UI/UX, FE |
| 2026-11-05 | Tambah metadata GPS lengkap di penilaian (waktu, jarak, radius) | Untuk audit trail dan deteksi kecurangan | UI/UX |
| 2026-11-05 | Tambah token 6 digit untuk verifikasi penilaian | Memastikan pembimbing industri sudah input nilai sebelum siswa verifikasi | UI/UX |
| 2026-11-05 | Bulk approve pengajuan sudah ada, tidak perlu action | Fitur sudah diimplementasikan di Sprint 3 | Back-End |
| 2026-11-05 | Reset sandi ke NIP/NIS sudah ada, tidak perlu action | Fitur sudah diimplementasikan di Sprint 2 | Back-End |

---

**Prepared by:** Backend Developer + AI Assistant  
**Date:** 5 November 2026  
**Version:** 1.0  
**Branch:** `rev/catatan-uiux-fe-1`
