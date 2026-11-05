# Ringkasan Eksekutif: Tindak Lanjut Catatan UI/UX & Front-End

**Tanggal:** 5 November 2026  
**Status:** Sprint 4 (Presensi Harian) ✅ Selesai  
**Branch:** `rev/catatan-uiux-fe-1`  
**Prepared by:** Backend Developer

---

## 📋 Ringkasan

Setelah Sprint 4 selesai, tim UI/UX dan Front-End Web memberikan catatan penting yang perlu ditindaklanjuti sebelum melanjutkan ke Sprint 5 (Jurnal Harian) dan Sprint 6 (Penilaian Bulanan). Dokumen ini merangkum hasil analisis dan rencana eksekusi.

**Dokumen Lengkap:**
- `.agents/Analisis_Catatan_UIUX_FE_Sprint_4-5.md` - Analisis lengkap 20+ halaman
- `.agents/Action_Plan_Catatan_UIUX_FE.md` - Action plan detail fase demi fase
- `docs/Update_Dokumentasi_Sprint_5-6.md` - Petunjuk update dokumentasi

---

## ✅ Catatan UI/UX: Status

| # | Catatan | Status | Keterangan |
|---|---------|--------|------------|
| 1 | Endpoint bulk approve pengajuan | ✅ Sudah Ada | Implemented di Sprint 3, `PUT /api/pengajuan-pkl/bulk-approve` |
| 2 | Penilaian: simpan waktu, koordinat, jarak, flag radius | ⚠️ Perlu Update | Tambah 3 field di `penilaian_bulanan` |
| 3 | Hapus approval jurnal, admin boleh edit | ⚠️ Perlu Perubahan | Revisi requirement Sprint 5 |
| 4 | Reset sandi ke NIP/NIS | ✅ Sudah Ada | Implemented di Sprint 2, `PUT /api/users/{id}/password` |

---

## 🔧 Perubahan Requirement Utama

### 1. Jurnal Harian: TANPA Approval ⚠️

**Sebelumnya:**
- Siswa input jurnal → Guru approve/revise → Jurnal ter-lock

**Sekarang (sesuai mockup):**
- Siswa input jurnal → Bisa edit kapan saja
- Admin bisa edit semua jurnal
- Guru hanya view (read-only)

**Impact:**
- Hapus 2 endpoint: `/api/jurnal/{id}/approve` dan `/api/jurnal/{id}/revise`
- Hapus 5 field di tabel: `status_review`, `catatan_guru`, `reviewed_by`, `reviewed_at`, `is_editable`
- Tambah 2 field: `edited_by`, `edited_at` (tracking)
- Update dashboard guru (hapus metric "jurnal perlu review")

**Estimasi Sprint 5:** 4 hari (hemat 1 hari dari 5 hari)

---

### 2. Penilaian: Metadata GPS Lengkap + Token 6 Digit 🆕

**Tambah di tabel `penilaian_bulanan`:**

| Field Baru | Type | Purpose |
|---|---|---|
| `verifikasi_waktu` | timestamp | Waktu upload foto (dari EXIF atau server) |
| `jarak_dari_pkl` | decimal(8,2) | Jarak dalam meter dari lokasi PKL (Haversine) |
| `dalam_radius` | boolean | Flag apakah verifikasi dalam radius |
| `token_verifikasi` | varchar(6) | Token 6 digit untuk verifikasi siswa |
| `token_generated_at` | timestamp | Kapan token dibuat |
| `token_used_at` | timestamp | Kapan token digunakan |

**Tambah di tabel `perusahaan`:**

| Field Baru | Type | Default | Purpose |
|---|---|---|---|
| `radius_presensi` | int unsigned | 100 | Radius dalam meter untuk validasi |
| `pembimbing_industri` | varchar(255) | NULL | Nama pembimbing dari industri |
| `bidang_usaha` | varchar(100) | NULL | Sektor bisnis perusahaan |

**Flow Baru:**
1. Guru input nilai → Generate token 6 digit → Kirim ke pembimbing industri (manual, di luar sistem)
2. Siswa terima token → Input token + upload foto + GPS
3. Sistem validasi token → Hitung jarak → Set flag radius → Simpan metadata
4. Token expire 24 jam atau setelah dipakai

**Impact:**
- Tambah 1 endpoint: `POST /api/penilaian/{id}/generate-token`
- Update endpoint: `POST /api/penilaian/{id}/verifikasi` (tambah validasi token + GPS)
- Implementasi Haversine distance formula

**Estimasi Sprint 6:** 6 hari (tambah 1 hari dari 5 hari)

---

## 📊 Gap dari Front-End Web

Dari file `.agents/Catatan_FE_untuk_BE_Simakin.md`:

### Kritis untuk Sprint 5-6:

| Gap | Impact | Action |
|---|---|---|
| Tabel `jurnal_harian` belum ada | 🔴 High | Create migration (Task 5.1) |
| Tabel `penilaian_bulanan` belum ada | 🔴 High | Create migration (Task 6.1) |
| Field `radius_presensi` di `perusahaan` | 🟡 Medium | Alter table (sebelum Sprint 6) |
| Token 6 digit belum ada | 🟡 Medium | Tambah field + endpoint (Task 6.3a) |

### Informasi Saja (tidak blocking):

- Beberapa field UI belum di BE (`is_active`, `bidang_keahlian`, dll) → Defer ke sprint mendatang
- Penamaan berbeda tapi sudah nyambung (`sikap` = "Soft Skill", `lokasi_pkl` = "Penempatan")

---

## 📅 Timeline Eksekusi

### Immediate (Hari 1-2) - 5-6 November 2026

**Action:**
1. ✅ Buat dokumen analisis & action plan (DONE)
2. ⏳ Review dengan PM, UI/UX, FE (0.5 hari)
3. ⏳ Update 4 file dokumentasi:
   - `backend-roadmap.md` (Sprint 5 & 6)
   - `002-database-schema.md` (perusahaan, jurnal_harian, penilaian_bulanan)
   - `003-api-endpoints.md` (jurnal, penilaian)
   - `PRD.md` (scope & pain points)

**Deliverable:** Dokumentasi ter-update, siap untuk implementasi

---

### Database Preparation (Hari 3) - 7 November 2026

**Action:**
1. Migration: Alter table `perusahaan` (tambah 3 field)
2. Migration: Create table `jurnal_harian` (struktur baru)
3. Update seeder `PerusahaanSeeder`
4. Create seeder `JurnalHarianSeeder`
5. Test migration di development
6. Commit & push

**Deliverable:** Database schema siap untuk Sprint 5

---

### Sprint 5: Jurnal Harian (Hari 4-7) - 8-11 November 2026

**Sesuai roadmap yang direvisi:**
- Task 5.1: Tabel & endpoint (1 hari)
- Task 5.2: CRUD jurnal tanpa approval (1.5 hari)
- Task 5.3: Riwayat & rekap (1 hari)
- Task 5.4: API docs (0.5 hari)

**Deliverable:** Jurnal harian API lengkap (8 endpoints)

---

### Sprint 6: Penilaian Bulanan (Hari 8-13) - 12-17 November 2026

**Sesuai roadmap yang direvisi:**
- Task 6.1: 4 tabel penilaian + alter perusahaan (1.5 hari)
- Task 6.2: Aktivasi penilaian (0.5 hari)
- Task 6.3: Input penilaian (1 hari)
- Task 6.3a: Token verifikasi (0.5 hari) **BARU**
- Task 6.4: Verifikasi dengan GPS + token (1.5 hari) **UPDATE**
- Task 6.5: Nilai akhir (0.5 hari)
- Task 6.6: API docs (0.5 hari)

**Deliverable:** Penilaian bulanan API lengkap (12 endpoints)

---

## ⚠️ Risk & Mitigation

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| Sprint 6 melebihi estimasi (6 hari vs 5 hari) | High | Medium | Defer comprehensive testing ke Sprint 8, fokus happy path |
| Haversine distance tidak akurat indoor | Low | Low | Simpan jarak sebagai info saja, tidak memblokir verifikasi |
| Token verifikasi menambah kompleksitas | Medium | Medium | Token bisa dibypass jika pembimbing tidak dapat dihubungi (PAD 1) |

---

## 🎯 Decision Points (Perlu Konfirmasi)

### 1. Approval Jurnal: HAPUS atau KEEP?

**Rekomendasi:** HAPUS (sesuai mockup UI/UX)

**Alasan:**
- Mockup mobile tidak ada indikator status review
- Mockup web admin ada tombol "Edit" (bukan approve/reject)
- Workflow lebih sederhana: admin langsung edit jika ada kesalahan

**Impact jika KEEP:**
- Tambah 2 endpoint + 5 field database
- Tambah 1 hari di Sprint 5
- Mockup perlu direvisi

**➡️ Mohon konfirmasi dari PM & UI/UX**

---

### 2. Token Verifikasi: WAJIB atau OPSIONAL?

**Rekomendasi:** OPSIONAL di PAD 1

**Alasan:**
- Mockup jelas menunjukkan input token 6 digit
- Tapi pengiriman token ke pembimbing industri (via WA/Email) di luar scope sistem PAD 1
- Bisa jadi blocker jika pembimbing industri tidak dapat dihubungi

**Proposal:**
- Implementasi penuh (generate token, validasi token)
- Tapi tambah fallback: admin bisa bypass token jika ada kendala

**➡️ Mohon konfirmasi dari PM & UI/UX**

---

## 📦 Deliverables

### Dokumen (✅ DONE)
- [x] Analisis lengkap catatan UI/UX & FE (20 halaman)
- [x] Action plan fase demi fase
- [x] Petunjuk update dokumentasi Sprint 5-6
- [x] Commit ke branch `rev/catatan-uiux-fe-1`

### Database (⏳ TODO)
- [ ] Migration: alter `perusahaan`
- [ ] Migration: create `jurnal_harian`
- [ ] Seeder: update & tambah

### Dokumentasi (⏳ TODO)
- [ ] Update `backend-roadmap.md`
- [ ] Update `002-database-schema.md`
- [ ] Update `003-api-endpoints.md`
- [ ] Update `PRD.md`

### Implementation (⏳ SCHEDULED)
- [ ] Sprint 5: Jurnal Harian (8-11 Nov)
- [ ] Sprint 6: Penilaian Bulanan (12-17 Nov)

---

## 🔄 Next Steps

### Hari Ini (5 Nov, Sore)
1. **Meeting:** Review dokumen analisis dengan PM, UI/UX, FE Web
2. **Keputusan:** Konfirmasi 2 decision points (approval jurnal, token verifikasi)
3. **Assignment:** Bagi tugas update dokumentasi (bisa paralel)

### Besok (6 Nov)
1. Update dokumentasi (backend-roadmap, database schema, API endpoints, PRD)
2. Commit semua perubahan dokumentasi
3. Tag milestone: "Ready for Sprint 5"

### Lusa (7 Nov)
1. Buat migration (alter perusahaan, create jurnal_harian)
2. Update seeder
3. Test migration
4. Commit & push

### 8 Nov - Mulai Sprint 5 Implementation

---

## 📞 Contact

**Questions?**
- Backend: Angga (developer utama)
- UI/UX: [nama PIC UI/UX]
- Front-End Web: [nama PIC FE]
- Project Manager: [nama PM]

**Files:**
- Analisis lengkap: `.agents/Analisis_Catatan_UIUX_FE_Sprint_4-5.md`
- Action plan detail: `.agents/Action_Plan_Catatan_UIUX_FE.md`
- Update instructions: `docs/Update_Dokumentasi_Sprint_5-6.md`

---

**Status:** 🟢 Analisis Complete, Ready for Review  
**Last Updated:** 5 November 2026, 14:52 WIB  
**Branch:** `rev/catatan-uiux-fe-1`  
**Commit:** Latest
