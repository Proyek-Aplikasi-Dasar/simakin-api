# Rencana Eksekusi: Update Dokumentasi Sprint 5-6

Dokumen ini berisi command untuk update dokumentasi sesuai catatan UI/UX dan FE.
Eksekusi manual setelah review.

## 1. Update Backend Roadmap Sprint 5

File: .agents/tasks/backend-roadmap.md
Line: 333-379

Ganti section Sprint 5 dengan:

## Sprint 5: Jurnal Harian (11-17 Nov)

**Goal:** Siswa input jurnal, **TIDAK ADA approval guru**, admin boleh edit semua jurnal.

**⚠️ PERUBAHAN dari requirement awal:** Berdasarkan catatan UI/UX tanggal 5 Nov 2026, approval jurnal dihapus untuk menyederhanakan workflow. Siswa bisa edit jurnal kapan saja, admin bisa edit semua jurnal, guru hanya bisa view (read-only).

### Task 5.1: Tabel & Endpoint Jurnal
- [ ] Migration: create_jurnal_harian_table (UNIQUE siswa_id+tanggal)
- [ ] Model: JurnalHarian (relasi: Siswa, Kompetensi, User (edited_by))
- [ ] Field: edited_by, edited_at (tracking siapa & kapan terakhir edit)
- [ ] **HAPUS field:** status_review, catatan_guru, reviewed_by, reviewed_at, is_editable
- [ ] Seeder: sample jurnal
- [ ] Controller: JurnalHarianController
- [ ] Endpoint: GET list (filter), GET/{id}
- [ ] File upload: foto dokumentasi

**Estimasi:** 1 hari

### Task 5.2: CRUD Jurnal (Siswa & Admin)
- [ ] POST /api/jurnal - create (siswa)
- [ ] Business: validasi presensi hadir hari ini (ABSENSI_REQUIRED)
- [ ] PUT /api/jurnal/{id} - Siswa edit milik sendiri kapan saja, Admin edit semua jurnal
- [ ] DELETE /api/jurnal/{id} - Siswa delete milik sendiri, Admin delete semua
- [ ] POST /api/jurnal/upload-foto (retry foto)
- [ ] Authorization: Policy update & delete (owner atau admin)
- [ ] Test: create + edit + delete + authorization

**Estimasi:** 1.5 hari

### Task 5.3: Riwayat & Rekap (TANPA status review)
- [ ] GET /api/jurnal/riwayat - kronologis per tanggal (asc/desc)
- [ ] GET /api/jurnal/rekap - total jurnal per siswa (tanpa breakdown approved/pending)
- [ ] Test

**Estimasi:** 1 hari

### Task 5.4: API Docs + Postman
- [ ] Update dokumentasi Sprint 5

**Estimasi:** 0.5 hari

**Sprint 5 Total: 4 hari** (buffer 1 hari)

---

## 2. Update Backend Roadmap Sprint 6

File: .agents/tasks/backend-roadmap.md
Line: 382-438

Tambah catatan di Task 6.1:

### Task 6.1: Tabel Penilaian
- [ ] Migration: create_aktivasi_penilaian_table
- [ ] Migration: create_penilaian_bulanan_table
  - **Field tambahan:** verifikasi_waktu, jarak_dari_pkl, dalam_radius
  - **Field token:** token_verifikasi (UNIQUE), token_generated_at, token_used_at
- [ ] Migration: create_penilaian_kompetensi_table
- [ ] Migration: create_penilaian_sikap_table
- [ ] Migration: alter perusahaan ADD radius_presensi, pembimbing_industri, bidang_usaha
- [ ] Models + relasi (HasMany detail)
- [ ] Seeder: sample data penilaian

**Estimasi:** 1.5 hari (ditambah 0.5 hari dari 1 hari)

Dan tambah Task 6.3a (baru):

### Task 6.3a: Token Verifikasi Penilaian
- [ ] POST /api/penilaian/{id}/generate-token (guru/admin) - generate token 6 digit
- [ ] Logic: random 6 digit, simpan token_verifikasi, set token_generated_at
- [ ] Token expire 24 jam
- [ ] Test: generate token + validation

**Estimasi:** 0.5 hari

Update Task 6.4:

### Task 6.4: Verifikasi Foto (Siswa) dengan Token + GPS Metadata
- [ ] POST /api/penilaian/{id}/verifikasi - upload foto + validasi token + GPS metadata
- [ ] Logic:
  - Validasi token (exists, not expired, not used)
  - Upload foto verifikasi
  - Hitung jarak_dari_pkl menggunakan Haversine formula (dari GPS siswa ke perusahaan PKL)
  - Set dalam_radius berdasarkan perusahaan.radius_presensi
  - Simpan verifikasi_waktu dari EXIF foto atau timestamp upload
  - Mark token_used_at = now()
  - Set verified_at = now()
- [ ] Response: id, verified_at, jarak_dari_pkl, dalam_radius, pesan
- [ ] Error: TOKEN_INVALID, TOKEN_EXPIRED, TOKEN_USED
- [ ] File upload validation
- [ ] Test: verifikasi flow lengkap + token validation

**Estimasi:** 1.5 hari (ditambah 0.5 hari dari 1 hari)

---

**Sprint 6 Total: 6 hari** (melebihi window 5 hari, perlu extend 1 hari atau defer test ke Sprint 8)

