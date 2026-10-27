# Skema Database SIMAKIN

**Source of truth:** `docs/database-erd.xml` (drawio) + revisi dari tech spec ini.

> **Catatan Revisi dari ERD asli:**
> 1. Tabel `user` → kolom `username`/`nama` diganti `email`/`name` (sudah diimplementasikan di Laravel). **Revisi 2026-10:** `username` dikembalikan sebagai identitas login (unique), `email` menjadi nullable dan tidak dipakai untuk login
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
| username | varchar(255) | NO | - | UNIQUE | **Identitas login** (siswa = NIS, guru = NIP, admin = username kustom) |
| email | varchar(255) | YES | NULL | UNIQUE | Email (opsional, **tidak dipakai** untuk login di PAD 1) |
| email_verified_at | timestamp | YES | NULL | - | - |
| password | varchar(255) | NO | - | - | Hashed password |
| role_id | bigint unsigned | NO | - | FK | → role.id |
| remember_token | varchar(100) | YES | NULL | - | - |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `role_id` → `role(id)`

**Business Rules:**
- Login memakai `username` + password (bukan email); username unik
- Siswa hanya bisa login via mobile (`X-Client: mobile`)
- Admin/guru bisa login via web (session) atau mobile (token)
- Registrasi: admin membuat akun guru/admin via `POST /api/register`; akun siswa dibuat lewat menu data siswa
- Email siswa diisi saat onboarding dan disimpan di `users.email` sebagai data kontak (bukan login/notifikasi)
- Ubah kata sandi: pengguna ubah sendiri via `PUT /api/user/password`; setelah sukses token/sesi lain dicabut
- Lupa kata sandi: tidak ada reset via email; admin mereset via `PUT /api/users/{id}/password`

**Status:** ✅ Sudah ada (migration + model + seeder)

---

## 3. Tabel `jurusan`

**Deskripsi:** Master jurusan/keahlian di SMK.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| nama | varchar(100) | NO | - | - | Nama jurusan (e.g., "Teknik Otomotif") |
| kode | varchar(20) | YES | NULL | - | Kode jurusan (e.g., "TKR") |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Admin full CRUD
- Guru/siswa read-only
- Data seeder: 10 jurusan resmi SMKN 1 Magelang

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint)

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
- Data seeder: 2 gelombang tahun ajaran 2026/2027

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint)

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
- Admin CRUD + bulk upload (bulk upload menyusul di Sprint 2)
- Siswa pilih kompetensi saat input jurnal harian
- Data seeder: 5 kompetensi per jurusan

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint CRUD)

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
- Admin CRUD + bulk upload (bulk upload menyusul di Sprint 2)
- `kuota` digunakan untuk validasi pengajuan PKL
- Guru/siswa read-only (filter by jurusan)
- Data seeder: 12 perusahaan + relasi jurusan (pivot)

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint CRUD + attach/detach jurusan)

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

**Status:** ✅ Sudah ada (migration + seeder + endpoint attach/detach)

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

**Business Rules:**
- Admin CRUD, guru/siswa read-only
- Data seeder: 10 sikap penilaian PKL

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint)

---

## 9. Tabel `waktu_absen`

**Deskripsi:** Konfigurasi jam kerja PKL.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| mulai | time | NO | - | - | Jam masuk (e.g., "06:00:00") |
| selesai | time | NO | - | - | Jam selesai (e.g., "22:00:00") |
| keterangan | varchar(255) | YES | NULL | - | Keterangan (e.g., "Jam kerja normal") |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Business Rules:**
- Digunakan untuk validasi keterlambatan presensi
- `selesai` harus lebih besar dari `mulai` (validasi `after:mulai`)
- Admin CRUD (tanpa endpoint detail)
- Data seeder: 06:00:00–22:00:00

**Status:** ✅ Sudah ada (migration + model + seeder + endpoint)

---

## 10. Tabel `guru`

**Deskripsi:** Data detail guru pembimbing.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| user_id | bigint unsigned | NO | - | FK, UNIQUE | → users.id |
| nip | varchar(30) | YES | NULL | UNIQUE | Nomor Induk Pegawai |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE

**Business Rules:**
- Create guru otomatis create `users` record (role_id = 2)
- Identitas login guru = `nip`; `users.username` selalu disinkronkan dengan `nip` saat create/update
- Admin CRUD + bulk upload Excel (password default `password123`, error per baris)
- NIP unique jika diisi; NIP juga harus unik di `users.username`
- Delete = **hard delete** transaksional: hapus `guru` + `users` (token mobile dicabut) sehingga NIP/username bisa dipakai ulang
- Relasi `hasMany(LokasiPkl)` ditambahkan pada Sprint 3 saat tabel `lokasi_pkl` tersedia

**Status:** Sudah ada (migration + model + seeder + endpoint CRUD + bulk upload)

---

## 11. Tabel `siswa`

**Deskripsi:** Data detail siswa PKL. Sebagian besar kolom profil diisi siswa saat onboarding (setelah login pertama) lalu dikonfirmasi.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| user_id | bigint unsigned | NO | - | FK, UNIQUE | → users.id |
| jurusan_id | bigint unsigned | YES | NULL | FK | → jurusan.id (diisi saat onboarding) |
| gelombang_id | bigint unsigned | YES | NULL | FK | → gelombang.id (1 siswa = 1 gelombang; diisi saat onboarding) |
| nis | varchar(20) | NO | - | UNIQUE | Nomor Induk Siswa (read-only dari `users.username`) |
| jenis_kelamin | enum('L','P') | YES | NULL | - | Laki-laki / Perempuan (diisi saat onboarding) |
| kelas | varchar(20) | YES | NULL | - | Kelas, mis. `XII TJKT 1` (diisi saat onboarding) |
| telp | varchar(20) | YES | NULL | - | No HP/WA siswa |
| alamat | text | YES | NULL | - | Alamat domisili siswa |
| nama_ortu | varchar(255) | YES | NULL | - | Nama orang tua/wali |
| telp_ortu | varchar(20) | YES | NULL | - | No HP/WA orang tua/wali |
| alamat_ortu | text | YES | NULL | - | Alamat domisili orang tua/wali |
| profil_confirmed_at | timestamp | YES | NULL | INDEX | Waktu siswa mengonfirmasi profil |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |
| deleted_at | timestamp | YES | NULL | - | Soft delete |

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE
- `jurusan_id` → `jurusan(id)` ON DELETE RESTRICT (nullable)
- `gelombang_id` → `gelombang(id)` ON DELETE RESTRICT (nullable)

**Business Rules:**
- Create siswa otomatis create `users` record (role_id = 3); `users.name` wajib diisi, `username` = NIS
- NIS unik dan sama dengan `users.username`; perubahan NIS hanya oleh admin dan harus menyinkronkan `siswa.nis` + `users.username`
- Profil awal boleh NULL: `jurusan_id`, `gelombang_id`, `jenis_kelamin`, `kelas`, `telp`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`; `users.email` diisi saat onboarding
- Tahun ajaran **tidak** disimpan di `siswa`; ditampilkan dari `gelombang.tahun_ajaran`
- Konfirmasi profil (`PUT /api/siswa/me/konfirmasi`) hanya berhasil jika seluruh field wajib terisi; jika belum → `PROFIL_BELUM_LENGKAP` (422)
- Setelah `profil_confirmed_at` terisi, siswa tidak dapat mengubah profil (`PROFIL_SUDAH_DIKONFIRMASI`, 422); perubahan hanya oleh admin (validasi sekolah)
- Admin update (`PUT /api/siswa/{id}`) tidak mereset `profil_confirmed_at`
- Satu user hanya punya satu baris `siswa` (`user_id` unik) → satu siswa hanya bisa 1 gelombang
- Admin CRUD + bulk upload Excel

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
| 3 | `jurusan` | ✅ Ada | ERD |
| 4 | `gelombang` | ✅ Ada | ERD |
| 5 | `kompetensi` | ✅ Ada | ERD |
| 6 | `perusahaan` | ✅ Ada | ERD + tambah telp, email, kuota |
| 7 | `perusahaan_jurusan` | ✅ Ada | ERD |
| 8 | `sikap` | ✅ Ada | ERD |
| 9 | `waktu_absen` | ✅ Ada | ERD + tambah keterangan |
| 10 | `guru` | ✅ Sudah ada | ERD |
| 11 | `siswa` | ✅ Sudah ada | ERD + onboarding (alamat, alamat_ortu, profil_confirmed_at) |
| 12 | `pengajuan_pkl` | ❌ Baru | Tambahan (belum ada di ERD) |
| 13 | `lokasi_pkl` | ❌ Belum | ERD (status enum revisi) |
| 14 | `absensi_siswa` | ❌ Belum | ERD (revisi: tanpa jam_keluar/foto_keluar) |
| 15 | `jurnal_harian` | ❌ Belum | ERD (tambah review fields) |
| 16 | `aktivasi_penilaian` | ❌ Baru | Tambahan (belum ada di ERD) |
| 17 | `penilaian_bulanan` | ❌ Baru | Revisi dari `penilaian_gelombang` |
| 18 | `penilaian_kompetensi` | ❌ Baru | Revisi dari ERD |
| 19 | `penilaian_sikap` | ❌ Baru | Revisi dari ERD |
| 20 | `notifications` | ❌ Baru | Laravel default |

**Total: 20 tabel** (11 sudah ada, 9 belum)

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
  1. create_jurusan_table ✅
  2. create_gelombang_table ✅
  3. create_sikap_table ✅
  4. create_waktu_absen_table ✅
  5. create_perusahaan_table ✅
  6. create_perusahaan_jurusan_table (FK: perusahaan, jurusan) ✅
  7. create_kompetensi_table (FK: jurusan) ✅

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
