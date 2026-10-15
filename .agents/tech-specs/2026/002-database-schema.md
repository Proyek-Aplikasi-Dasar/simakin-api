# Skema Database SIMAKIN

**Source of truth:** `docs/database-erd.xml` (drawio) + revisi dari tech spec ini.

> **Catatan Revisi dari ERD asli:**
> 1. Tabel `user` → kolom `username`/`nama` diganti `email`/`name` (sudah diimplementasikan di Laravel)
> 2. Tabel `absensi_siswa`: tanpa `jam_keluar` dan `foto_keluar` (hanya check-in)
> 3. Tambahan tabel `pengajuan_pkl` (alur pengajuan PKL siswa)
> 4. Tambahan tabel `aktivasi_penilaian` (kontrol periode penilaian 1-10)
> 5. Struktur penilaian dipecah: `penilaian_bulanan` (master) + `penilaian_kompetensi` + `penilaian_sikap` (detail)
> 6. Tabel `jurnal_harian`: tambah `deskripsi`, `foto`, `status_review`, `catatan_guru`, `reviewed_by`, `reviewed_at`, `is_editable`

## Convention

| Item | Convention |
|---|---|
| Table name | Singular lowercase (`siswa`, `lokasi_pkl`) |
| Column name | snake_case (`user_id`, `created_at`) |
| Primary key | `id` (bigint unsigned auto-increment) |
| Foreign key | `{table}_id` |
| Pivot table | Alphabetical order (`perusahaan_jurusan`) |
| Timestamps | `created_at`, `updated_at` |
| Soft delete | `deleted_at` (opsional, untuk data master) |
| Audit trail | `created_by`, `updated_by` (untuk tabel master) |
| Charset | utf8mb4 / utf8mb4_unicode_ci |
| Engine | InnoDB |

---

## 1. Tabel `role`

**Deskripsi:** Daftar role pengguna.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| nama | varchar(50) | NO | - | - | Nama role: `admin`, `guru`, `siswa` |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Status:** ✅ Sudah ada (migration + model + seeder)

---

## 2. Tabel `users`

**Deskripsi:** Akun autentikasi semua pengguna (admin, guru, siswa).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| name | varchar(255) | NO | - | - | Nama lengkap |
| email | varchar(255) | NO | - | UNIQUE | Email (login identifier) |
| email_verified_at | timestamp | YES | NULL | - | - |
| password | varchar(255) | NO | - | - | Hashed password |
| role_id | bigint unsigned | NO | - | FK | → role.id |
| remember_token | varchar(100) | YES | NULL | - | - |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `role_id` → `role(id)`

**Business Rules:**
- Siswa hanya bisa login via mobile (`X-Client: mobile`)
- Admin/guru bisa login via web (session) atau mobile (token)
- Registrasi: hanya admin yang bisa create user siswa
- Reset password: via email token

**Status:** ✅ Sudah ada (migration + model + seeder)

---

## 3. Tabel `jurusan`

**Deskripsi:** Master jurusan/keahlian di SMK.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| nama | varchar(100) | NO | - | - | Nama jurusan (e.g., "Teknik Komputer dan Jaringan") |
| kode | varchar(20) | YES | NULL | - | Kode jurusan (e.g., "TKJ") |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Admin full CRUD
- Guru/siswa read-only

---

## 4. Tabel `gelombang`

**Deskripsi:** Gelombang/periode PKL (tahun ajaran + jadwal).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| tahun_ajaran | varchar(20) | NO | - | - | e.g., "2026/2027" |
| nama | varchar(100) | NO | - | - | e.g., "Gelombang 1" |
| daftar_mulai | date | NO | - | - | Awal pendaftaran |
| daftar_selesai | date | NO | - | - | Akhir pendaftaran |
| praktik_mulai | date | NO | - | - | Awal masa PKL |
| praktik_selesai | date | NO | - | - | Akhir masa PKL |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Satu siswa hanya bisa ikut **1 gelombang** (unique `siswa_id` di tabel siswa)
- Pendaftaran hanya terbuka saat `daftar_mulai <= today <= daftar_selesai`

---

## 5. Tabel `kompetensi`

**Deskripsi:** Master kompetensi/keahlian per jurusan (kurikulum).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| jurusan_id | bigint unsigned | NO | - | FK | → jurusan.id |
| nama | varchar(255) | NO | - | - | Nama kompetensi |
| kode | varchar(50) | YES | NULL | - | Kode kompetensi (opsional) |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `jurusan_id` → `jurusan(id)` ON DELETE CASCADE

**Business Rules:**
- Admin CRUD + bulk upload
- Siswa pilih kompetensi saat input jurnal harian

---

## 6. Tabel `perusahaan`

**Deskripsi:** Data industri/perusahaan tempat PKL.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| nama | varchar(255) | NO | - | - | Nama perusahaan |
| alamat | text | NO | - | - | Alamat lengkap |
| latitude | decimal(10,8) | YES | NULL | - | Koordinat GPS latitude |
| longitude | decimal(11,8) | YES | NULL | - | Koordinat GPS longitude |
| pimpinan | varchar(255) | YES | NULL | - | Nama pimpinan/HRD |
| telp | varchar(20) | YES | NULL | - | Nomor telepon |
| email | varchar(255) | YES | NULL | - | Email perusahaan |
| deskripsi | text | YES | NULL | - | Deskripsi/visi perusahaan |
| kuota | int unsigned | YES | NULL | - | Maksimal siswa diterima |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Admin CRUD + bulk upload
- `kuota` digunakan untuk validasi pengajuan PKL
- Guru/siswa read-only (filter by jurusan)

---

## 7. Tabel `perusahaan_jurusan` (Pivot)

**Deskripsi:** Relasi many-to-many: perusahaan ↔ jurusan (jurusan yang diterima di perusahaan).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| perusahaan_id | bigint unsigned | NO | - | PK composite, FK | → perusahaan.id |
| jurusan_id | bigint unsigned | NO | - | PK composite, FK | → jurusan.id |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `perusahaan_id` → `perusahaan(id)` ON DELETE CASCADE
- `jurusan_id` → `jurusan(id)` ON DELETE CASCADE

---

## 8. Tabel `sikap`

**Deskripsi:** Master soft skill/sikap untuk penilaian.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| nama | varchar(100) | NO | - | - | Nama sikap (e.g., "Disiplin", "Tanggung Jawab") |
| deskripsi | text | YES | NULL | - | Deskripsi sikap |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

---

## 9. Tabel `waktu_absen`

**Deskripsi:** Konfigurasi jam kerja PKL.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| mulai | time | NO | - | - | Jam masuk (e.g., "08:00:00") |
| selesai | time | NO | - | - | Jam selesai (e.g., "16:00:00") |
| keterangan | varchar(255) | YES | NULL | - | Keterangan (e.g., "Jam kerja normal") |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Digunakan untuk validasi keterlambatan presensi
- Admin CRUD

---

## 10. Tabel `guru`

**Deskripsi:** Data detail guru pembimbing.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| user_id | bigint unsigned | NO | - | FK, UNIQUE | → users.id |
| nip | varchar(30) | YES | NULL | UNIQUE | Nomor Induk Pegawai |
| mata_pelajaran | varchar(100) | YES | NULL | - | Mata pelajaran yang diampu |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE

**Business Rules:**
- Create guru otomatis create `users` record (role_id = 2)
- Admin CRUD + bulk upload Excel
- NIP unique jika diisi

---

## 11. Tabel `siswa`

**Deskripsi:** Data detail siswa PKL.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| user_id | bigint unsigned | NO | - | FK, UNIQUE | → users.id |
| jurusan_id | bigint unsigned | NO | - | FK | → jurusan.id |
| gelombang_id | bigint unsigned | NO | - | FK | → gelombang.id (1 siswa = 1 gelombang) |
| nis | varchar(20) | NO | - | UNIQUE | Nomor Induk Siswa |
| jenis_kelamin | enum('L','P') | NO | - | - | Laki-laki / Perempuan |
| kelas | varchar(10) | NO | - | - | Kelas (A, B, C, D, E) |
| telp | varchar(20) | YES | NULL | - | No telepon siswa |
| nama_ortu | varchar(255) | YES | NULL | - | Nama orang tua/wali |
| telp_ortu | varchar(20) | YES | NULL | - | No telepon orang tua |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |
| deleted_at | timestamp | YES | NULL | - | Soft delete |

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE
- `jurusan_id` → `jurusan(id)` ON DELETE RESTRICT
- `gelombang_id` → `gelombang(id)` ON DELETE RESTRICT

**Business Rules:**
- Create siswa otomatis create `users` record (role_id = 3)
- NIS unique
- Satu siswa hanya bisa 1 gelombang (unik di `user_id`)
- Admin CRUD + bulk upload Excel
- Tidak ada kolom `alamat` (sesuai konfirmasi)

---

## 12. Tabel `pengajuan_pkl`

**Deskripsi:** Pengajuan tempat PKL oleh siswa.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK | → siswa.id |
| perusahaan_id | bigint unsigned | NO | - | FK | → perusahaan.id |
| gelombang_id | bigint unsigned | NO | - | FK | → gelombang.id |
| status | enum('pending','approved','rejected') | NO | 'pending' | - | Status pengajuan |
| alasan_penolakan | text | YES | NULL | - | Alasan ditolak (jika rejected) |
| verified_by | bigint unsigned | YES | NULL | FK | → users.id (admin/guru yang verifikasi) |
| verified_at | timestamp | YES | NULL | - | Waktu verifikasi |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `perusahaan_id` → `perusahaan(id)` ON DELETE CASCADE
- `gelombang_id` → `gelombang(id)` ON DELETE CASCADE
- `verified_by` → `users(id)` ON DELETE SET NULL

**Business Rules:**
- Siswa hanya bisa punya 1 pengajuan `pending` per gelombang
- Validasi kuota perusahaan (kuota sudah penuh → reject)
- Admin/guru bisa approve/reject
- Bulk approve: terima array of IDs
- Siswa bisa cancel (delete) jika masih `pending`

---

## 13. Tabel `lokasi_pkl`

**Deskripsi:** Penempatan siswa PKL (hasil approval pengajuan / assign manual admin).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK | → siswa.id |
| guru_id | bigint unsigned | YES | NULL | FK | → guru.id (guru pembimbing) |
| perusahaan_id | bigint unsigned | NO | - | FK | → perusahaan.id |
| gelombang_id | bigint unsigned | NO | - | FK | → gelombang.id |
| status | enum('pending','active','completed','cancelled') | NO | 'pending' | - | Status penempatan |
| catatan | text | YES | NULL | - | Catatan admin/guru |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `guru_id` → `guru(id)` ON DELETE SET NULL
- `perusahaan_id` → `perusahaan(id)` ON DELETE CASCADE
- `gelombang_id` → `gelombang(id)` ON DELETE CASCADE

**Business Rules:**
- Satu siswa hanya 1 lokasi PKL `active` per gelombang
- Status flow: `pending` → `active` → `completed` / `cancelled`
- Admin CRUD + bulk approve dari pengajuan
- Guru pembimbing ditugaskan saat siswa `active`

**Perubahan dari ERD asli:**
- Status enum lebih deskriptif (bukan "register, practice")
- Tambah `gelombang_id` (traceability ke periode PKL)

---

## 14. Tabel `absensi_siswa`

**Deskripsi:** Presensi harian siswa (check-in dengan foto + GPS).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK | → siswa.id |
| tanggal | date | NO | - | UNIQUE composite | Tanggal presensi |
| status | enum('hadir','izin','sakit','libur') | NO | 'hadir' | - | Status kehadiran |
| latitude | decimal(10,8) | YES | NULL | - | GPS latitude saat check-in |
| longitude | decimal(11,8) | YES | NULL | - | GPS longitude saat check-in |
| foto_masuk | varchar(500) | YES | NULL | - | Path foto selfie (relatif storage) |
| jam_masuk | time | YES | NULL | - | Waktu check-in (server timestamp) |
| keterangan | text | YES | NULL | - | Keterangan untuk izin/sakit/libur |
| status_submit | enum('success','failed') | NO | 'success' | - | Status pengiriman |
| dikoreksi_oleh | bigint unsigned | YES | NULL | FK | → users.id (admin/guru yang koreksi) |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `dikoreksi_oleh` → `users(id)` ON DELETE SET NULL

**Constraints:**
- UNIQUE(`siswa_id`, `tanggal`) - anti double submit

**Business Rules:**
- Siswa check-in via mobile (foto + GPS)
- **Tanpa** `jam_keluar` dan `foto_keluar` (hanya check-in)
- `status_submit = 'failed'` → siswa bisa retry/update
- `dikoreksi_oleh` terisi saat admin/guru melakukan koreksi manual
- Jam masuk dicatat server-side (bukan dari client)
- Keterlambatan dihitung berdasarkan `waktu_absen.mulai`

**Perubahan dari ERD asli:**
- Hapus: `jam_keluar`, `foto_keluar` (sesuai konfirmasi)
- Tambah: `jam_masuk`, `status_submit`, `dikoreksi_oleh`
- `lokasi (maps)` dipisah: `latitude` + `longitude`

---

## 15. Tabel `jurnal_harian`

**Deskripsi:** Jurnal kegiatan harian siswa PKL.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK | → siswa.id |
| kompetensi_id | bigint unsigned | NO | - | FK | → kompetensi.id |
| tanggal | date | NO | - | UNIQUE composite | Tanggal jurnal |
| deskripsi | text | NO | - | - | Deskripsi kegiatan (unlimited) |
| foto | varchar(500) | YES | NULL | - | Path foto dokumentasi (opsional) |
| status_review | enum('pending','approved','need_revision') | NO | 'pending' | - | Status review guru |
| catatan_guru | text | YES | NULL | - | Feedback/catatan dari guru |
| reviewed_by | bigint unsigned | YES | NULL | FK | → users.id (guru pembimbing) |
| reviewed_at | timestamp | YES | NULL | - | Waktu direview |
| is_editable | boolean | NO | true | - | Siswa bisa edit (false setelah approved) |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `kompetensi_id` → `kompetensi(id)` ON DELETE RESTRICT
- `reviewed_by` → `users(id)` ON DELETE SET NULL

**Constraints:**
- UNIQUE(`siswa_id`, `tanggal`) - 1 jurnal per siswa per hari

**Business Rules:**
- Siswa input setelah presensi `hadir`
- `deskripsi` = `text` type (unlimited characters - pain point #7)
- Guru review: approve (`is_editable = false`) atau need_revision (`is_editable = true`)
- Siswa bisa edit hanya jika `is_editable = true`
- Riwayat jurnal urut kronologis per tanggal
- Bulk review guru (opsional): terima array

**Perubahan dari ERD asli:**
- Tambah: `deskripsi`, `foto`, `status_review`, `catatan_guru`, `reviewed_by`, `reviewed_at`, `is_editable`
- ERD asli hanya: `id`, `siswa_id`, `kompetensi_id`, `tanggal`, `pekerjaan`

---

## 16. Tabel `aktivasi_penilaian`

**Deskripsi:** Kontrol periode input penilaian bulanan (tgl 1-10 setiap bulan).

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| gelombang_id | bigint unsigned | NO | - | FK | → gelombang.id |
| bulan | tinyint unsigned | NO | - | - | Bulan (1-12) |
| tahun | smallint unsigned | NO | - | - | Tahun (e.g., 2026) |
| mulai | date | NO | - | - | Tanggal mulai input |
| selesai | date | NO | - | - | Tanggal akhir input |
| is_active | boolean | NO | true | - | Periode aktif/tidak |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `gelombang_id` → `gelombang(id)` ON DELETE CASCADE

**Constraints:**
- UNIQUE(`gelombang_id`, `bulan`, `tahun`)

**Business Rules:**
- Admin buka/tutup periode penilaian
- Default periode: tanggal 1-10 setiap bulan
- Penilaian hanya bisa diinput saat `is_active = true`

**Perubahan dari ERD asli:**
- Tabel baru (tidak ada di ERD)

---

## 17. Tabel `penilaian_bulanan`

**Deskripsi:** Master penilaian bulanan per siswa.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK | → siswa.id |
| aktivasi_penilaian_id | bigint unsigned | NO | - | FK | → aktivasi_penilaian.id |
| bulan | tinyint unsigned | NO | - | - | Bulan (1-12) |
| tahun | smallint unsigned | NO | - | - | Tahun |
| nilai_akhir | decimal(5,2) | YES | NULL | - | Rata-rata dari detail (0-100) |
| catatan | text | YES | NULL | - | Catatan umum |
| input_by | bigint unsigned | NO | - | FK | → users.id (guru/industri) |
| foto_verifikasi | varchar(500) | YES | NULL | - | Foto verifikasi (swafoto + watermark) |
| verifikasi_lat | decimal(10,8) | YES | NULL | - | GPS saat verifikasi |
| verifikasi_long | decimal(11,8) | YES | NULL | - | GPS saat verifikasi |
| verified_at | timestamp | YES | NULL | - | Waktu verifikasi |
| notified_guru_at | timestamp | YES | NULL | - | Notifikasi ke guru terkirim |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `aktivasi_penilaian_id` → `aktivasi_penilaian(id)` ON DELETE CASCADE
- `input_by` → `users(id)` ON DELETE RESTRICT

**Constraints:**
- UNIQUE(`siswa_id`, `bulan`, `tahun`)

**Business Rules:**
- Input oleh guru/industri (via akun siswa)
- `nilai_akhir` = rata-rata kompetensi + sikap (dihitung otomatis)
- Verifikasi: siswa upload foto dengan watermark GPS+timestamp (di mobile)
- Saat penilaian diinput → notifikasi ke guru pembimbing
- Anti-kecurangan: `foto_verifikasi` wajib, `verifikasi_lat/long` dari GPS device

**Perubahan dari ERD asli:**
- ERD punya `penilaian_gelombang` (per bulan, tanpa relasi siswa) → dipecah jadi `penilaian_bulanan` per siswa
- Tambah: `input_by`, `foto_verifikasi`, `verifikasi_lat/long`, `verified_at`, `notified_guru_at`

---

## 18. Tabel `penilaian_kompetensi` (Detail)

**Deskripsi:** Nilai per kompetensi untuk penilaian bulanan.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| penilaian_bulanan_id | bigint unsigned | NO | - | FK | → penilaian_bulanan.id |
| kompetensi_id | bigint unsigned | NO | - | FK | → kompetensi.id |
| nilai | decimal(5,2) | NO | - | - | Nilai (0-100) |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `penilaian_bulanan_id` → `penilaian_bulanan(id)` ON DELETE CASCADE
- `kompetensi_id` → `kompetensi(id)` ON DELETE RESTRICT

**Constraints:**
- UNIQUE(`penilaian_bulanan_id`, `kompetensi_id`)

**Perubahan dari ERD asli:**
- ERD punya `penilaian_kompetensi` dengan relasi langsung ke `siswa` dan `gelombang_id` → dipecah jadi detail dari `penilaian_bulanan`

---

## 19. Tabel `penilaian_sikap` (Detail)

**Deskripsi:** Nilai sikap/soft skill untuk penilaian bulanan.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| penilaian_bulanan_id | bigint unsigned | NO | - | FK | → penilaian_bulanan.id |
| sikap_id | bigint unsigned | NO | - | FK | → sikap.id |
| nilai | decimal(5,2) | NO | - | - | Nilai (0-100) |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `penilaian_bulanan_id` → `penilaian_bulanan(id)` ON DELETE CASCADE
- `sikap_id` → `sikap(id)` ON DELETE RESTRICT

**Constraints:**
- UNIQUE(`penilaian_bulanan_id`, `sikap_id`)

---

## 20. Tabel `notifications` (Laravel Default)

**Deskripsi:** Notifikasi database (Laravel Notification).

Menggunakan tabel default Laravel. Tidak perlu migration custom.

**Status:** Belum ada migration (akan ditambahkan jika pakai `php artisan make:notification`)

---

## Ringkasan Tabel

| # | Table | Status | Sumber |
|---|---|---|---|
| 1 | `role` | ✅ Ada | Laravel setup |
| 2 | `users` | ✅ Ada | Laravel setup |
| 3 | `jurusan` | ❌ Belum | ERD |
| 4 | `gelombang` | ❌ Belum | ERD |
| 5 | `kompetensi` | ❌ Belum | ERD |
| 6 | `perusahaan` | ❌ Belum | ERD + tambah telp, email, kuota |
| 7 | `perusahaan_jurusan` | ❌ Belum | ERD |
| 8 | `sikap` | ❌ Belum | ERD |
| 9 | `waktu_absen` | ❌ Belum | ERD + tambah keterangan |
| 10 | `guru` | ❌ Belum | ERD + tambah mata_pelajaran |
| 11 | `siswa` | ❌ Belum | ERD (tanpa alamat) |
| 12 | `pengajuan_pkl` | ❌ Baru | Tambahan (belum ada di ERD) |
| 13 | `lokasi_pkl` | ❌ Belum | ERD (status enum revisi) |
| 14 | `absensi_siswa` | ❌ Belum | ERD (revisi: tanpa jam_keluar/foto_keluar) |
| 15 | `jurnal_harian` | ❌ Belum | ERD (tambah review fields) |
| 16 | `aktivasi_penilaian` | ❌ Baru | Tambahan (belum ada di ERD) |
| 17 | `penilaian_bulanan` | ❌ Baru | Revisi dari `penilaian_gelombang` |
| 18 | `penilaian_kompetensi` | ❌ Baru | Revisi dari ERD |
| 19 | `penilaian_sikap` | ❌ Baru | Revisi dari ERD |
| 20 | `notifications` | ❌ Baru | Laravel default |

**Total: 20 tabel** (2 sudah ada, 18 belum)

---

## Migration Order

Mengikuti dependency (foreign key harus sudah ada):

```
Phase 1 (sudah ada):
  0001_cache_table
  0002_jobs_table
  personal_access_tokens
  roles ✅
  users ✅
  sessions ✅

Phase 2 (data master, tanpa FK ke Phase 2+):
  1. create_jurusan_table
  2. create_gelombang_table
  3. create_sikap_table
  4. create_waktu_absen_table
  5. create_perusahaan_table
  6. create_perusahaan_jurusan_table (FK: perusahaan, jurusan)
  7. create_kompetensi_table (FK: jurusan)

Phase 3 (user detail):
  8. create_guru_table (FK: users)
  9. create_siswa_table (FK: users, jurusan, gelombang)

Phase 4 (PKL):
  10. create_pengajuan_pkl_table (FK: siswa, perusahaan, gelombang, users)
  11. create_lokasi_pkl_table (FK: siswa, guru, perusahaan, gelombang)

Phase 5 (harian):
  12. create_absensi_siswa_table (FK: siswa, users)
  13. create_jurnal_harian_table (FK: siswa, kompetensi, users)

Phase 6 (penilaian):
  14. create_aktivasi_penilaian_table (FK: gelombang)
  15. create_penilaian_bulanan_table (FK: siswa, aktivasi_penilaian, users)
  16. create_penilaian_kompetensi_table (FK: penilaian_bulanan, kompetensi)
  17. create_penilaian_sikap_table (FK: penilaian_bulanan, sikap)
```

---

## Entity Relationship Diagram (Revisi)

```
role (1) ────── (many) users ────── (1) guru
                     │                    │
                     │ (1)                │ (many)
                     │                    ▼
                     │              lokasi_pkl (many) ◄────── (1) perusahaan
                     │                 │    │                    │
                     │                 │    │ (many)             │ (many)
                     │                 │    ▼                    ▼
                     │                 │  absensi_siswa     perusahaan_jurusan
                     │                 │  jurnal_harian           │
                     │                 │                          │ (many)
                     │                 │ (many)                   ▼
                     │                 ▼                    jurusan (1) ── (many) kompetensi
                     │            penilaian_bulanan              ▲
                     │                 │    │                    │
                     │        (many)   │    │ (many)             │
                     │                 ▼    ▼                    │
                     │      penilaian_kompetensi  penilaian_sikap │
                     │                           │               │
                     │ (1)                       │ (1)           │
                     ▼                           ▼               │
                   siswa ──── (1) pengajuan_pkl ──── (many)      │
                     │              │                             │
                     │ (many)       │ (1)                         │
                     │              ▼                             │
                     └──── gelombang ─────────────────────────────┘
                               │
                               ▼
                        aktivasi_penilaian
```

**Master tables (untuk admin CRUD):** `jurusan`, `gelombang`, `kompetensi`, `perusahaan`, `sikap`, `waktu_absen`

**User detail:** `guru`, `siswa` (1-to-1 dengan `users`)

**PKL flow:** `pengajuan_pkl` → `lokasi_pkl`

**Harian:** `absensi_siswa`, `jurnal_harian`

**Penilaian:** `aktivasi_penilaian` → `penilaian_bulanan` → (`penilaian_kompetensi` + `penilaian_sikap`)
