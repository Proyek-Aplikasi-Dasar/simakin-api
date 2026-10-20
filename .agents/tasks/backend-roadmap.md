# Backend Roadmap - SIMAKIN (PAD 1)

**Timeline:** 9 Oktober - 12 Desember 2026 (9 minggu)
**Target:** Demo-ready MVP backend
**Developer:** 1 Back-End + AI assistant

---

## Sprint Overview

| Sprint | Periode | Fokus | Estimasi |
|---|---|---|---|
| 0 | 9-13 Okt | Auth lengkap + Fondasi | 5 hari |
| 1 | 14-20 Okt | Data Master (7 tabel) | 5 hari |
| 2 | 21-27 Okt | Data Master User (Guru + Siswa + Excel) | 5 hari |
| 3 | 28 Okt - 3 Nov | Pengajuan & Penempatan PKL | 5 hari |
| 4 | 4-10 Nov | Presensi Harian | 5 hari |
| 5 | 11-17 Nov | Jurnal Harian | 5 hari |
| 6 | 18-24 Nov | Penilaian Bulanan | 5 hari |
| 7 | 25 Nov - 1 Des | Notifikasi + Dashboard + Rekap | 5 hari |
| 8 | 2-8 Des | Integration Test + Bug Fixing | 5 hari |
| 9 | 9-12 Des | Final Polish + Demo Prep | 4 hari |

**Total: 44 hari kerja, buffer ~4 hari** (beberapa task bisa overlap/diparalel)

---

## Sprint 0: Auth Lengkap + Fondasi (9-13 Okt)

**Goal:** Autentikasi lengkap (login, logout, register, ubah kata sandi), dokumentasi siap, siap mulai coding fitur.

### Task 0.1: Perbaikan Environment
- [x] Update `.env.example` ke MySQL (uncomment + lengkapi DB config)
- [x] Tambah `ADMIN_URL` (jika perlu terpisah dari `FRONTEND_URL`)
- [x] Set `SESSION_DRIVER=database` (agar sesi web bisa dicabut saat ubah kata sandi)
- [x] Verifikasi `phpunit.xml` pakai SQLite in-memory untuk testing
- [x] Jalankan `php artisan migrate --seed` dengan MySQL untuk verifikasi

**Estimasi:** 0.5 hari

### Task 0.2: Registrasi (Register)
- [x] `POST /api/register` - Create user (guru/admin) oleh admin
- [x] Form Request: `RegisterRequest` (validasi username unique, role_id exists)
- [x] Auto-create profil tergantung role (guru → tabel `guru`)
- [x] Seeder update jika perlu
- [x] Feature test: admin can register guru, siswa cannot register

**Estimasi:** 0.5 hari

### Task 0.3: Ubah Kata Sandi & Cabut Sesi
- [x] `PUT /api/user/password` - Ubah kata sandi sendiri (web & mobile)
- [x] Form Request: `UpdatePasswordRequest` (`current_password`, confirmed, min 8)
- [x] Cabut semua token mobile lain + sesi web lain setelah sukses
- [x] Feature test: ubah sandi, sandi lama salah, konfirmasi beda, password lemah, revoke token
- [ ] (Sprint 2) Admin reset kata sandi user `PUT /api/users/{id}/password` sebagai jalur lupa sandi

**Catatan:** Tidak ada reset via email di PAD 1.

**Estimasi:** 1 hari

### Task 0.4: Middleware Role Guard (Refactor)
- [x] Review & test middleware `role:admin,guru,siswa`
- [x] Pastikan dipakai di routes yang sudah ada
- [x] Test: siswa cannot access admin routes

**Estimasi:** 0.5 hari

### Task 0.5: API Docs Update
- [x] Update `docs/API-Docs-fondasi.md` dengan register + ubah kata sandi
- [ ] Mulai Postman collection (folder 01. Authentication)

**Estimasi:** 0.5 hari

### Task 0.6: Pahami User Flow (untuk konteks)
- [ ] Review PDF user flow (admin, guru, siswa) - convert ke teks jika perlu
- [ ] Review mockup penting untuk Sprint 1-2

**Estimasi:** 0.5 hari (bisa paralel)

**Sprint 0 Total: ~3.5 hari** (buffer 1.5 hari untuk dokumentasi/roadmap yang sedang dikerjakan)

---

## Sprint 1: Data Master Core (14-20 Okt)

**Goal:** 7 tabel master + CRUD + Seeder. Foundation untuk semua modul lain.

### Task 1.1: Jurusan
- [ ] Migration: `create_jurusan_table`
- [ ] Model: `Jurusan` (fillable: nama, kode)
- [ ] Seeder: Data jurusan SMKN 1 Magelang (TKJ, RPL, MM, TKR, TBSM, etc.)
- [ ] Controller: `JurusanController` (resource)
- [ ] Request: `StoreJurusanRequest`, `UpdateJurusanRequest`
- [ ] Resource: `JurusanResource`
- [ ] Routes: `Route::apiResource('jurusan', ...)` - middleware `auth:sanctum`
- [ ] Policy: admin CRUD, any read
- [ ] Test: CRUD + authorization

**Estimasi:** 0.5 hari

### Task 1.2: Kompetensi
- [ ] Migration: `create_kompetensi_table` (FK: jurusan)
- [ ] Model: `Kompetensi` (relasi: belongsTo Jurusan)
- [ ] Seeder: Data kompetensi per jurusan (minimal 5 per jurusan)
- [ ] Controller + Request + Resource
- [ ] Route: `GET /api/jurusan/{id}/kompetensi` (nested)
- [ ] Test: CRUD + filter by jurusan_id

**Estimasi:** 0.5 hari

### Task 1.3: Gelombang
- [ ] Migration: `create_gelombang_table`
- [ ] Model: `Gelombang` (casts: dates)
- [ ] Seeder: 1-2 gelombang sample (2026/2027)
- [ ] Controller + Request + Resource
- [ ] Test: CRUD + validasi tanggal (daftar_mulai < daftar_selesai)

**Estimasi:** 0.5 hari

### Task 1.4: Perusahaan + Pivot
- [ ] Migration: `create_perusahaan_table`
- [ ] Migration: `create_perusahaan_jurusan_table`
- [ ] Model: `Perusahaan` (relasi: belongsToMany Jurusan)
- [ ] Seeder: 10-15 perusahaan sample
- [ ] Controller + Request + Resource
- [ ] Endpoint: `POST /api/perusahaan/{id}/jurusan`, `DELETE .../{jurusanId}`
- [ ] Query: `GET /api/perusahaan?jurusan_id=X` (filter jurusan)
- [ ] Test: CRUD + attach/detach jurusan + filter

**Estimasi:** 1 hari

### Task 1.5: Sikap
- [ ] Migration: `create_sikap_table`
- [ ] Model: `Sikap`
- [ ] Seeder: Disiplin, Tanggung Jawab, Kerjasama, Inisiatif, Kedisiplinan, dll
- [ ] Controller + Request + Resource
- [ ] Test: CRUD

**Estimasi:** 0.5 hari

### Task 1.6: Waktu Absen
- [ ] Migration: `create_waktu_absen_table`
- [ ] Model: `WaktuAbsen`
- [ ] Seeder: 08:00-16:00
- [ ] Controller + Request + Resource
- [ ] Test: CRUD

**Estimasi:** 0.5 hari

### Task 1.7: Bulk Upload Foundation
- [ ] Install `maatwebsite/excel` atau `openspout/openspout`
- [ ] Buat Import class base + error handling pattern
- [ ] Template Excel download endpoint (opsional)
- [ ] Test: parse Excel valid + handle invalid rows

**Estimasi:** 1 hari (fondasi, dipakai di Sprint 2)

**Sprint 1 Total: 4.5 hari** (buffer 0.5 hari)

---

## Sprint 2: User Detail - Guru & Siswa + Excel (21-27 Okt)

**Goal:** Tabel guru & siswa lengkap dengan bulk upload Excel.

### Task 2.1: Guru CRUD
- [ ] Migration: `create_guru_table` (FK: users, UNIQUE nip)
- [ ] Model: `Guru` (relasi: belongsTo User, hasMany LokasiPkl)
- [ ] Seeder: 5 guru sample
- [ ] Controller: `GuruController`
- [ ] Store logic: create `users` (role_id=2, **username = NIP**) + create `guru` dalam transaction
- [ ] Bulk upload: `POST /api/guru/bulk-upload` (Excel)
- [ ] Endpoint: `GET /api/guru/{id}/siswa-bimbingan`
- [ ] Test: CRUD guru + bulk upload + auto-create user

**Estimasi:** 1.5 hari

### Task 2.2: Siswa CRUD
- [ ] Migration: `create_siswa_table` (FK: users, jurusan, gelombang)
- [ ] Model: `Siswa` (relasi: belongsTo User, Jurusan, Gelombang)
- [ ] Seeder: 20 siswa sample
- [ ] Controller: `SiswaController`
- [ ] Store logic: create `users` (role_id=3, **username = NIS**) + create `siswa` dalam transaction
- [ ] Bulk upload: `POST /api/siswa/bulk-upload` (Excel, ~700 rows)
- [ ] Endpoint: `GET /api/siswa/me` (profil siswa login)
- [ ] Validation: NIS unique, username unique (= NIS), gelombang exists
- [ ] Test: CRUD siswa + bulk upload + validation NIS duplikat

**Estimasi:** 2 hari

### Task 2.3: User Management Endpoints
- [ ] `GET /api/users` (list + filter role + search + pagination)
- [ ] `GET /api/users/{id}`
- [ ] `PUT /api/users/{id}`
- [ ] `PUT /api/users/{id}/password` (admin reset kata sandi — **jalur lupa sandi**, cabut sesi user)
- [ ] `DELETE /api/users/{id}` (soft delete)
- [ ] Test: CRUD user + reset password + authorization

**Estimasi:** 1 hari

### Task 2.4: Excel Template
- [ ] Buat template Excel untuk: siswa, guru, perusahaan, kompetensi
- [ ] Endpoint download template (opsional, bisa file statis di docs/)
- [ ] Dokumentasi format kolom di `docs/API-Docs-fondasi.md` atau file terpisah

**Estimasi:** 0.5 hari

**Sprint 2 Total: 5 hari** (pas, tanpa buffer)

---

## Sprint 3: Pengajuan & Penempatan PKL (28 Okt - 3 Nov)

**Goal:** Alur PKL: siswa ajukan → admin approve → penempatan aktif.

### Task 3.1: Tabel Pengajuan PKL
- [ ] Migration: `create_pengajuan_pkl_table`
- [ ] Model: `PengajuanPkl` (relasi: Siswa, Perusahaan, Gelombang, User)
- [ ] Seeder: sample pengajuan
- [ ] Controller: `PengajuanPklController`
- [ ] Endpoint: GET, GET/{id}, POST (siswa), PUT approve, PUT reject, DELETE (cancel)
- [ ] Business logic:
  - Siswa hanya bisa 1 pengajuan pending per gelombang (`PENGAJUAN_DUPLICATE`)
  - Validasi gelombang aktif (`GELOMBANG_NOT_ACTIVE`)
  - Validasi kuota perusahaan (`PERUSAHAAN_KUOTA_PENUH`)
  - Validasi jurusan siswa sesuai perusahaan
- [ ] Bulk approve: `PUT /api/pengajuan-pkl/bulk-approve` (array IDs)
- [ ] Notifikasi: approve/reject → database notification ke siswa
- [ ] Test: full flow + setiap error code

**Estimasi:** 2.5 hari

### Task 3.2: Lokasi PKL (Penempatan)
- [ ] Migration: `create_lokasi_pkl_table`
- [ ] Model: `LokasiPkl` (relasi: Siswa, Guru, Perusahaan, Gelombang)
- [ ] Seeder: sample penempatan
- [ ] Controller: `LokasiPklController`
- [ ] Endpoint: CRUD + `PUT /{id}/status`
- [ ] Business logic:
  - Satu siswa hanya 1 `active` (`SISWA_ALREADY_PLACED`)
  - Status flow: pending → active → completed/cancelled
- [ ] Auto-create dari pengajuan approve (link pengajuan → lokasi)
- [ ] Test: CRUD + status flow + validation

**Estimasi:** 1.5 hari

### Task 3.3: Dashboard Admin (Basic)
- [ ] `GET /api/dashboard/admin` - stats: total siswa/guru/perusahaan, pengajuan pending
- [ ] Cache 10 menit
- [ ] Test

**Estimasi:** 0.5 hari

### Task 3.4: API Docs + Postman
- [ ] Update dokumentasi Sprint 3 endpoints
- [ ] Update Postman collection

**Estimasi:** 0.5 hari

**Sprint 3 Total: 5 hari**

---

## Sprint 4: Presensi Harian (4-10 Nov)

**Goal:** Siswa bisa check-in dengan foto + GPS, admin bisa koreksi.

### Task 4.1: Tabel & Endpoint Presensi
- [ ] Migration: `create_absensi_siswa_table` (UNIQUE siswa_id+tanggal)
- [ ] Model: `AbsensiSiswa` (relasi: Siswa, User (dikoreksi_oleh))
- [ ] Seeder: sample presensi beberapa hari
- [ ] Controller: `AbsensiSiswaController`
- [ ] Endpoint: GET list (filter), GET/{id}
- [ ] File upload: foto ke `storage/uploads/presensi/{date}/`
- [ ] Validation: foto image 5MB, lat/long numeric, keterangan required jika != hadir

**Estimasi:** 1 hari

### Task 4.2: Check-in Logic
- [ ] `POST /api/absensi` (siswa check-in)
- [ ] Business rules:
  - Validasi gelombang/lokasi PKL aktif (`GELOMBANG_NOT_ACTIVE`)
  - Anti double submit: UNIQUE constraint (`ABSENSI_DUPLICATE`)
  - Server-side jam_masuk timestamp
  - Response: data + `next_action: /jurnal/create`
- [ ] `POST /api/absensi/upload-foto` (retry foto terpisah)
- [ ] `PUT /api/absensi/{id}` (edit jika status_submit=failed)
- [ ] `DELETE /api/absensi/{id}` (batalkan presensi)
- [ ] Test: check-in success, duplicate, invalid status

**Estimasi:** 1.5 hari

### Task 4.3: Koreksi Admin/Guru
- [ ] `POST /api/absensi/{id}/koreksi` (admin/guru)
- [ ] Logic: update status, catatan, `dikoreksi_oleh` = auth user
- [ ] Notifikasi ke siswa saat dikoreksi
- [ ] Test: koreksi + authorization

**Estimasi:** 0.5 hari

### Task 4.4: Rekap Presensi
- [ ] `GET /api/absensi/rekap` - summary per siswa (hadir/izin/sakit/libur count)
- [ ] `GET /api/absensi/siswa/{id}/periode` - rekap bulanan per siswa
- [ ] Query: filter tanggal range, gelombang, jurusan
- [ ] Test: rekap akurat

**Estimasi:** 1 hari

### Task 4.5: API Docs + Postman
- [ ] Update dokumentasi Sprint 4
- [ ] Update Postman collection

**Estimasi:** 0.5 hari (bisa digabung)

**Sprint 4 Total: 4.5 hari** (buffer 0.5 hari)

---

## Sprint 5: Jurnal Harian (11-17 Nov)

**Goal:** Siswa input jurnal, guru review (approve/revise).

### Task 5.1: Tabel & Endpoint Jurnal
- [ ] Migration: `create_jurnal_harian_table` (UNIQUE siswa_id+tanggal)
- [ ] Model: `JurnalHarian` (relasi: Siswa, Kompetensi, User (reviewed_by))
- [ ] Seeder: sample jurnal
- [ ] Controller: `JurnalHarianController`
- [ ] Endpoint: GET list (filter), GET/{id}
- [ ] File upload: foto dokumentasi

**Estimasi:** 1 hari

### Task 5.2: CRUD Jurnal (Siswa)
- [ ] `POST /api/jurnal` - create
- [ ] Business: validasi presensi `hadir` hari ini (`ABSENSI_REQUIRED`)
- [ ] `PUT /api/jurnal/{id}` - edit (jika `is_editable = true`)
- [ ] Business: `JURNAL_NOT_EDITABLE` jika sudah approved
- [ ] `DELETE /api/jurnal/{id}` - delete (jika pending)
- [ ] `POST /api/jurnal/upload-foto` (retry foto)
- [ ] Test: create + edit + delete + validation

**Estimasi:** 1.5 hari

### Task 5.3: Review Jurnal (Guru)
- [ ] `PUT /api/jurnal/{id}/approve` - set approved, is_editable=false
- [ ] `PUT /api/jurnal/{id}/revise` - set need_revision, catatan_guru
- [ ] Notifikasi ke siswa untuk kedua aksi
- [ ] Authorization: hanya guru (pembimbing siswa tsb) atau admin
- [ ] Test: approve flow + revise flow + notifikasi

**Estimasi:** 1 hari

### Task 5.4: Riwayat & Rekap
- [ ] `GET /api/jurnal/riwayat` - kronologis per tanggal (asc/desc)
- [ ] `GET /api/jurnal/rekap` - total/approved/pending/need_revision per siswa
- [ ] Test

**Estimasi:** 1 hari

### Task 5.5: API Docs + Postman
- [ ] Update dokumentasi Sprint 5

**Estimasi:** 0.5 hari

**Sprint 5 Total: 5 hari**

---

## Sprint 6: Penilaian Bulanan (18-24 Nov)

**Goal:** Input nilai kompetensi + sikap, verifikasi foto, anti-kecurangan.

### Task 6.1: Tabel Penilaian
- [ ] Migration: `create_aktivasi_penilaian_table`
- [ ] Migration: `create_penilaian_bulanan_table`
- [ ] Migration: `create_penilaian_kompetensi_table`
- [ ] Migration: `create_penilaian_sikap_table`
- [ ] Models + relasi (HasMany detail)
- [ ] Seeder: sample data penilaian

**Estimasi:** 1 hari

### Task 6.2: Aktivasi Periode (Admin)
- [ ] CRUD `aktivasi_penilaian` (buka/tutup periode)
- [ ] Validation: periode 1-10 tanggal default
- [ ] Test

**Estimasi:** 0.5 hari

### Task 6.3: Input Penilaian (Guru)
- [ ] `POST /api/penilaian` - input nilai bulanan
- [ ] Request: siswa_id, aktivasi_id, kompetensi[{id,nilai}], sikap[{id,nilai}]
- [ ] Logic:
  - Validasi periode aktif (`PERIODE_CLOSED`)
  - Bulk insert details
  - Hitung `nilai_akhir` (rata-rata)
  - Notifikasi ke guru pembimbing (`notified_guru_at`)
- [ ] `PUT /api/penilaian/{id}` - edit nilai
- [ ] `GET /api/penilaian` (list + filter)
- [ ] `GET /api/penilaian/{id}` (detail + nested details)
- [ ] Test: input success + periode closed + duplicate + notification

**Estimasi:** 1.5 hari

### Task 6.4: Verifikasi Foto (Siswa)
- [ ] `POST /api/penilaian/{id}/verifikasi` - upload foto + GPS metadata
- [ ] Logic: update foto_verifikasi, verifikasi_lat/long, verified_at
- [ ] File upload validation
- [ ] Test

**Estimasi:** 0.5 hari

### Task 6.5: Nilai Akhir
- [ ] `GET /api/penilaian/nilai-akhir/{siswaId}` - rekap semua bulan + rata-rata
- [ ] Test: perhitungan akurat

**Estimasi:** 0.5 hari

### Task 6.6: API Docs + Postman
- [ ] Update dokumentasi Sprint 6

**Estimasi:** 0.5 hari

**Sprint 6 Total: 4.5 hari** (buffer 0.5 hari)

---

## Sprint 7: Notifikasi + Dashboard + Rekap (25 Nov - 1 Des)

**Goal:** Notifikasi berjalan, dashboard guru lengkap, rekap Excel.

### Task 7.1: Database Notifications
- [ ] Laravel notifications table migration (jika belum)
- [ ] Notification classes:
  - `JurnalReviewedNotification` (ke siswa)
  - `PengajuanStatusNotification` (ke siswa)
  - `PenilaianInputNotification` (ke guru)
  - `AbsensiCorrectedNotification` (ke siswa)
- [ ] Wire ke trigger di Sprint 3-6 (jika belum)
- [ ] Endpoint notifications (4 endpoints: list, unread-count, read, read-all)
- [ ] Test: notifikasi ter-trigger + bisa di-list

**Estimasi:** 1.5 hari

### Task 7.2: Queue & Notifikasi Database
- [ ] Verifikasi queue config (`QUEUE_CONNECTION=database`)
- [ ] Pastikan notifikasi database dikirim via queue (`onQueue('notifications')`)
- [ ] Test: notifikasi masuk tabel `notifications`

**Catatan:** PAD 1 tidak mengirim email (tidak ada `ResetPasswordNotification`).

**Estimasi:** 0.5 hari

### Task 7.3: Dashboard Guru
- [ ] `GET /api/dashboard/guru/{id}` - ringkasan sekali pandang:
  - Total siswa bimbingan
  - Sudah/belum absen hari ini
  - Sudah/belum lapor jurnal hari ini
  - Jurnal perlu review
- [ ] Authorization: guru hanya lihat dashboard sendiri, admin bisa lihat semua
- [ ] Cache 5 menit
- [ ] Test

**Estimasi:** 1 hari

### Task 7.4: Dashboard Siswa
- [ ] `GET /api/dashboard/siswa/me` - status PKL, presensi/jurnal hari ini, ringkasan nilai
- [ ] Test

**Estimasi:** 0.5 hari

### Task 7.5: Rekap Excel
- [ ] Install `maatwebsite/excel` (jika belum di Sprint 1)
- [ ] `GET /api/rekap/excel/presensi` - export presensi
- [ ] `GET /api/rekap/excel/jurnal` - export jurnal
- [ ] `GET /api/rekap/excel/nilai` - export penilaian
- [ ] Query params: tanggal range, gelombang, jurusan, siswa
- [ ] Test: file generated, data akurat

**Estimasi:** 1.5 hari

**Sprint 7 Total: 5 hari**

---

## Sprint 8: Integration Testing & Bug Fixing (2-8 Des)

**Goal:** Semua endpoint teruji, bug diperbaiki, siap demo.

### Task 8.1: Integration Tests
- [ ] Full flow test: Register → Login → Create Master Data → Pengajuan → Approve → Presensi → Jurnal → Review → Penilaian
- [ ] Authorization matrix test: setiap role vs setiap endpoint
- [ ] Edge case: duplicate, invalid state, cross-user access
- [ ] Test coverage audit: pastikan semua endpoint ada test

**Estimasi:** 2 hari

### Task 8.2: API Documentation Final
- [ ] Review `docs/API-Docs-fondasi.md` (atau split per modul)
- [ ] Review `.agents/tech-specs/2026/003-api-endpoints.md` (sync dengan implementasi)
- [ ] Export Postman collection final
- [ ] Jalankan Collection Runner - pastikan semua pass

**Estimasi:** 1 hari

### Task 8.3: Bug Fixing & Refactoring
- [ ] Fix bugs dari testing
- [ ] Run `./vendor/bin/pint` (auto-format)
- [ ] Review error handling consistency
- [ ] Security check: input validation, authorization, file upload

**Estimasi:** 2 hari

**Sprint 8 Total: 5 hari**

---

## Sprint 9: Final Polish + Demo Prep (9-12 Des)

**Goal:** Demo-ready.

### Task 9.1: Seeder Realistis
- [ ] Seeder untuk demo: 100+ siswa, 20 guru, 30 perusahaan
- [ ] Sample data: presensi 1 bulan, jurnal, penilaian
- [ ] Script reset: `php artisan migrate:fresh --seed`

**Estimasi:** 0.5 hari

### Task 9.2: Environment & Config
- [ ] `.env.example` final review
- [ ] Production config checklist (di `001-architecture.md` bagian 10)
- [ ] `php artisan config:cache`, `route:cache` test

**Estimasi:** 0.5 hari

### Task 9.3: Documentation Final
- [ ] README.md update (setup instructions)
- [ ] Semua dokumentasi `.agents/` review final
- [ ] Postman collection export final

**Estimasi:** 0.5 hari

### Task 9.4: Demo Preparation
- [ ] Test full flow di environment bersih
- [ ] Postman collection untuk demo
- [ ] Siapkan data demo scenario

**Estimasi:** 1 hari

### Task 9.5: Buffer
- [ ] Time buffer untuk unexpected issues

**Estimasi:** 1.5 hari

**Sprint 9 Total: 4 hari**

---

## Milestones

| Date | Milestone | Check |
|---|---|---|
| 13 Okt | Auth lengkap + dokumentasi selesai | ☐ |
| 27 Okt | Data Master + Guru/Siswa + Excel selesai | ☐ |
| 3 Nov | Pengajuan + Penempatan PKL selesai | ☐ |
| 10 Nov | Presensi selesai | ☐ |
| 17 Nov | Jurnal Harian selesai | ☐ |
| 24 Nov | Penilaian selesai | ☐ |
| 1 Des | Notifikasi + Dashboard + Rekap selesai | ☐ |
| 8 Des | Integration test + bug fixing selesai | ☐ |
| 12 Des | **DEMO READY** | ☐ |

---

## Risk & Mitigation

| Risk | Impact | Mitigation |
|---|---|---|
| ERD berubah (menyesuaikan UI/UX) | Rework migration | Review ERD dgn UI/UX di awal Sprint 1 |
| Excel import kompleks (format tidak standar) | Delay Sprint 2 | Fokus core CRUD dulu, Excel di akhir sprint |
| Testing lama, scope melebar | Tidak selesai deadline | Prioritas sesuai to-do PAD 1, chat/push NOT PAD 1 |
| N+1 query / performa buruk | Dashboard lambat | Eager loading + caching di sprint terkait |
| Perubahan requirement dari tim | Redo | Dokumentasi jadi source of truth, discuss sebelum ubah |

---

## Cara Menggunakan Dokumen Ini

1. **Sprint planning:** Buka sprint berjalan, review tasks
2. **Saat coding:** Check off tasks yang selesai, commit dengan message `[BE] feat: ...`
3. **Blocker:** Catat di task comment, discuss dengan tim
4. **Review:** Setiap akhir sprint, review bersama tim
5. **Jika ERD/requirement berubah:** Update dokumen tech-spec dulu, lalu update task ini
