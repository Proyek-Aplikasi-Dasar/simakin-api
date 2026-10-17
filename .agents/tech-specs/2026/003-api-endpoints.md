# API Endpoints SIMAKIN

**Base URL:** `{base_url}/api` (development: `http://localhost:8000/api`)

**Konvensi:** Lihat `.agents/tech-specs/2026/001-architecture.md` bagian 2 (API Design Principles).

**Legend:**
- **Auth:** `Public` (tanpa login), `Required` (harus login)
- **Role:** `admin`, `guru`, `siswa`, `admin+guru`, `any` (semua role yang login)
- **Status:** `✅ Done`, `📋 Planned`

---

## 1. Authentication (5 endpoints)

### 1.1 POST /api/login ✅ Done

| | |
|---|---|
| Auth | Public |
| Role | any |

**Mobile** (header `X-Client: mobile`):
```json
{ "email": "siswa@example.com", "password": "siswa123" }
```
Response 200:
```json
{
  "user": { "id": 4, "name": "...", "email": "...", "role_id": 3, "role": { "id": 3, "nama": "siswa" } },
  "token": "1|abc..."
}
```

**Web** (tanpa `X-Client`, dengan CSRF):
```json
{ "email": "guru@example.com", "password": "guru123" }
```
Response 200:
```json
{
  "user": { "id": 3, "name": "...", "email": "...", "role_id": 2, "role": { "id": 2, "nama": "guru" } }
}
```

**Error:**
- 422: Kredensial tidak valid / Siswa hanya bisa login via mobile

**Dokumentasi lengkap:** `docs/API-Docs-fondasi.md`

### 1.2 POST /api/logout ✅ Done

| | |
|---|---|
| Auth | Required |
| Role | any |

Response 200: `{ "message": "Berhasil logout." }`

### 1.3 GET /api/user ✅ Done

| | |
|---|---|
| Auth | Required |
| Role | any |

Response 200:
```json
{
  "id": 4, "name": "...", "email": "...", "role_id": 3,
  "role": { "id": 3, "nama": "siswa" },
  "siswa": { ... }  // relasi tergantung role
}
```

### 1.4 POST /api/register ✅ Done

| | |
|---|---|
| Auth | Required |
| Role | admin |

Create user baru (guru/admin). Siswa: gunakan endpoint guru/siswa CRUD.

**Request:**
```json
{
  "name": "Guru Baru",
  "email": "guru.baru@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role_id": 2
}
```

Response 201:
```json
{
  "message": "Pengguna berhasil didaftarkan.",
  "data": {
    "id": 5, "name": "Guru Baru", "email": "guru.baru@example.com",
    "role_id": 2, "role": { "id": 2, "nama": "guru" }
  }
}
```

**Error:**
- 401: Belum login
- 403: Bukan admin
- 422: `role_id` bukan admin/guru, email duplikat, atau password tidak memenuhi syarat (min 8 + konfirmasi)

### 1.5 PUT /api/user/password ✅ Done

| | |
|---|---|
| Auth | Required |
| Role | any |

Ubah kata sandi sendiri (web & mobile). Setelah sukses, semua token mobile lain dan sesi web lain dicabut (kecuali sesi saat ini).

**Request:**
```json
{
  "current_password": "lama12345",
  "password": "baru12345",
  "password_confirmation": "baru12345"
}
```

Response 200: `{ "message": "Kata sandi berhasil diubah." }`

**Error:**
- 401: Belum login
- 422: `current_password` salah, konfirmasi tidak cocok, atau password kurang dari 8 karakter

> **Lupa kata sandi:** tidak ada alur reset via email. Pengguna yang tidak bisa login menghubungi admin; admin mereset kata sandi via `PUT /api/users/{id}/password` (bagian 2.4).

---

## 2. User Management (5 endpoints) 📋

### 2.1 GET /api/users

| | |
|---|---|
| Auth | Required |
| Role | admin |

**Query:** `role_id`, `search`, `page`, `per_page`

Response 200: paginated user list.

### 2.2 GET /api/users/{id}

| | |
|---|---|
| Auth | Required |
| Role | admin |

Response 200: user detail.

### 2.3 PUT /api/users/{id}

| | |
|---|---|
| Auth | Required |
| Role | admin |

Update user (name, email, role_id).

### 2.4 PUT /api/users/{id}/password

| | |
|---|---|
| Auth | Required |
| Role | admin |

Reset password user oleh admin. **Request:** `{ "password": "...", "password_confirmation": "..." }`

> **Jalur lupa kata sandi:** ini satu-satunya cara pemulihan ketika pengguna tidak bisa login (tidak ada reset via email). Setelah reset, sebaiknya cabut semua token/sesi milik user tsb.

### 2.5 DELETE /api/users/{id}

| | |
|---|---|
| Auth | Required |
| Role | admin |

Soft delete user. Response 200: `{ "message": "User berhasil dihapus." }`

---

## 3. Data Master - Jurusan (6 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/jurusan | Required | any | List jurusan |
| GET | /api/jurusan/{id} | Required | any | Detail jurusan |
| POST | /api/jurusan | Required | admin | Create jurusan |
| PUT | /api/jurusan/{id} | Required | admin | Update jurusan |
| DELETE | /api/jurusan/{id} | Required | admin | Delete jurusan |
| GET | /api/jurusan/{id}/kompetensi | Required | any | List kompetensi per jurusan |

**GET /api/jurusan** - Query: `search` (nama/kode)

**POST /api/jurusan** - Body: `{ "nama": "...", "kode": "..." }`

---

## 4. Data Master - Kompetensi (6 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/kompetensi | Required | any | List kompetensi |
| GET | /api/kompetensi/{id} | Required | any | Detail kompetensi |
| POST | /api/kompetensi | Required | admin | Create kompetensi |
| PUT | /api/kompetensi/{id} | Required | admin | Update kompetensi |
| DELETE | /api/kompetensi/{id} | Required | admin | Delete kompetensi |
| POST | /api/kompetensi/bulk-upload | Required | admin | Import Excel |

**GET /api/kompetensi** - Query: `jurusan_id`, `search`

**POST /api/kompetensi/bulk-upload** - Multipart: `file` (xlsx/csv)

Response 200:
```json
{
  "message": "Import berhasil: 15 data. Gagal: 2 data.",
  "success": 15,
  "failed": 2,
  "errors": [
    { "row": 3, "error": "Nama kompetensi kosong." }
  ]
}
```

---

## 5. Data Master - Gelombang (5 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/gelombang | Required | any | List gelombang |
| GET | /api/gelombang/{id} | Required | any | Detail gelombang |
| POST | /api/gelombang | Required | admin | Create gelombang |
| PUT | /api/gelombang/{id} | Required | admin | Update gelombang |
| DELETE | /api/gelombang/{id} | Required | admin | Delete gelombang |

**POST /api/gelombang** - Body:
```json
{
  "tahun_ajaran": "2026/2027",
  "nama": "Gelombang 1",
  "daftar_mulai": "2026-11-01",
  "daftar_selesai": "2026-11-30",
  "praktik_mulai": "2027-01-05",
  "praktik_selesai": "2027-06-30"
}
```

---

## 6. Data Master - Perusahaan (8 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/perusahaan | Required | any | List perusahaan |
| GET | /api/perusahaan/{id} | Required | any | Detail perusahaan |
| POST | /api/perusahaan | Required | admin | Create perusahaan |
| PUT | /api/perusahaan/{id} | Required | admin | Update perusahaan |
| DELETE | /api/perusahaan/{id} | Required | admin | Delete perusahaan |
| POST | /api/perusahaan/bulk-upload | Required | admin | Import Excel |
| POST | /api/perusahaan/{id}/jurusan | Required | admin | Attach jurusan |
| DELETE | /api/perusahaan/{id}/jurusan/{jurusanId} | Required | admin | Detach jurusan |

**GET /api/perusahaan** - Query: `jurusan_id` (filter jurusan), `search` (nama/alamat), `kuota_tersedia` (boolean)

**POST /api/perusahaan** - Body:
```json
{
  "nama": "...", "alamat": "...", "latitude": -7.xxx, "longitude": 110.xxx,
  "pimpinan": "...", "telp": "08xxx", "email": "...", "deskripsi": "...", "kuota": 10,
  "jurusan_ids": [1, 2, 3]
}
```

---

## 7. Data Master - Sikap (5 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/sikap | Required | any | List sikap |
| GET | /api/sikap/{id} | Required | any | Detail sikap |
| POST | /api/sikap | Required | admin | Create sikap |
| PUT | /api/sikap/{id} | Required | admin | Update sikap |
| DELETE | /api/sikap/{id} | Required | admin | Delete sikap |

---

## 8. Data Master - Waktu Absen (4 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/waktu-absen | Required | any | List waktu absen |
| POST | /api/waktu-absen | Required | admin | Create waktu absen |
| PUT | /api/waktu-absen/{id} | Required | admin | Update waktu absen |
| DELETE | /api/waktu-absen/{id} | Required | admin | Delete waktu absen |

**POST** - Body: `{ "mulai": "08:00", "selesai": "16:00", "keterangan": "Jam kerja normal" }`

---

## 9. Data Master - Guru (7 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/guru | Required | admin | List guru |
| GET | /api/guru/{id} | Required | admin, guru | Detail guru |
| POST | /api/guru | Required | admin | Create guru + user |
| PUT | /api/guru/{id} | Required | admin | Update guru |
| DELETE | /api/guru/{id} | Required | admin | Delete guru |
| POST | /api/guru/bulk-upload | Required | admin | Import Excel |
| GET | /api/guru/{id}/siswa-bimbingan | Required | admin, guru | List siswa bimbingan |

**POST /api/guru** - Body:
```json
{
  "name": "Nama Guru", "email": "guru@example.com",
  "password": "password123", "password_confirmation": "password123",
  "nip": "198701012010011001", "mata_pelajaran": "Produktif"
}
```
**Logic:** Create `users` (role_id=2) → Create `guru` (user_id).

**GET /api/guru/{id}/siswa-bimbingan** - Query: `status` (active/completed), `tanggal` (filter presensi/jurnal hari ini)

Response: siswa list + status hari ini:
```json
{
  "data": [
    {
      "siswa": { "id": 1, "nis": "001", "nama": "..." },
      "presensi_hari_ini": { "status": "hadir", "jam_masuk": "07:58" },
      "jurnal_hari_ini": { "status_review": "pending" }
    }
  ]
}
```

---

## 10. Data Master - Siswa (8 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/siswa | Required | admin, guru | List siswa |
| GET | /api/siswa/{id} | Required | admin, guru | Detail siswa |
| POST | /api/siswa | Required | admin | Create siswa + user |
| PUT | /api/siswa/{id} | Required | admin | Update siswa |
| DELETE | /api/siswa/{id} | Required | admin | Delete siswa |
| POST | /api/siswa/bulk-upload | Required | admin | Import Excel |
| GET | /api/siswa/me | Required | siswa | Data diri sendiri |
| GET | /api/siswa/{id}/lokasi-pkl | Required | admin, guru, siswa | Lokasi PKL siswa |

**GET /api/siswa** - Query: `jurusan_id`, `gelombang_id`, `kelas`, `search` (nis/nama), `status_pkl` (active/pending/none), `page`

**POST /api/siswa** - Body:
```json
{
  "name": "...", "email": "siswa@example.com",
  "password": "password123", "password_confirmation": "password123",
  "nis": "0012345", "jurusan_id": 1, "gelombang_id": 1,
  "jenis_kelamin": "L", "kelas": "XII-A",
  "telp": "08xxx", "nama_ortu": "...", "telp_ortu": "08xxx"
}
```

**GET /api/siswa/me** - Response: profil lengkap + status PKL + ringkasan hari ini.

---

## 11. Pengajuan PKL (6 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/pengajuan-pkl | Required | admin, guru, siswa | List pengajuan |
| GET | /api/pengajuan-pkl/{id} | Required | admin, guru, siswa | Detail pengajuan |
| POST | /api/pengajuan-pkl | Required | siswa | Ajukan PKL |
| PUT | /api/pengajuan-pkl/{id}/approve | Required | admin, guru | Approve pengajuan |
| PUT | /api/pengajuan-pkl/{id}/reject | Required | admin, guru | Reject pengajuan |
| DELETE | /api/pengajuan-pkl/{id} | Required | siswa | Cancel pengajuan (jika pending) |

**GET /api/pengajuan-pkl** - Query: `status` (pending/approved/rejected), `siswa_id`, `perusahaan_id`, `gelombang_id`, `page`

**POST /api/pengajuan-pkl** (siswa) - Body:
```json
{ "perusahaan_id": 5, "gelombang_id": 1 }
```
**Logic:** Validasi gelombang aktif → Validasi jurusan siswa sesuai perusahaan → Validasi belum ada pengajuan pending → Validasi kuota perusahaan.

**PUT /approve** - Body (opsional): `{ "guru_pembimbing_id": 3 }` (assign guru saat approve)

**PUT /reject** - Body: `{ "alasan_penolakan": "Kuota sudah penuh" }`

**Bulk Approve** (opsional, untuk admin):
```
PUT /api/pengajuan-pkl/bulk-approve
Body: { "ids": [1, 2, 3], "guru_pembimbing_id": 3 }
```

---

## 12. Lokasi PKL / Penempatan (6 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/lokasi-pkl | Required | admin, guru | List penempatan |
| GET | /api/lokasi-pkl/{id} | Required | admin, guru, siswa | Detail penempatan |
| POST | /api/lokasi-pkl | Required | admin | Assign siswa ke perusahaan |
| PUT | /api/lokasi-pkl/{id} | Required | admin | Update penempatan |
| PUT | /api/lokasi-pkl/{id}/status | Required | admin | Update status |
| DELETE | /api/lokasi-pkl/{id} | Required | admin | Delete penempatan |

**GET /api/lokasi-pkl** - Query: `status` (pending/active/completed/cancelled), `siswa_id`, `guru_id`, `perusahaan_id`, `gelombang_id`, `search`, `page`

**POST /api/lokasi-pkl** - Body:
```json
{
  "siswa_id": 1, "perusahaan_id": 5, "guru_id": 3, "gelombang_id": 1
}
```
**Logic:** Validasi siswa belum punya lokasi PKL active → create.

**PUT /status** - Body: `{ "status": "active" }`
**Flow:** `pending` → `active` → `completed` | `cancelled`

---

## 13. Presensi / Absensi (9 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/absensi | Required | admin, guru, siswa | List absensi |
| GET | /api/absensi/{id} | Required | admin, guru, siswa | Detail absensi |
| POST | /api/absensi | Required | siswa | Check-in |
| PUT | /api/absensi/{id} | Required | siswa | Edit (jika status_submit=failed) |
| DELETE | /api/absensi/{id} | Required | siswa, admin | Batalkan presensi |
| POST | /api/absensi/{id}/koreksi | Required | admin, guru | Koreksi manual |
| GET | /api/absensi/rekap | Required | admin, guru | Rekap presensi |
| GET | /api/absensi/siswa/{id}/periode | Required | admin, guru, siswa | Rekap bulanan per siswa |
| POST | /api/absensi/upload-foto | Required | siswa | Upload foto terpisah (retry) |

**GET /api/absensi** - Query: `siswa_id`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `status` (hadir/izin/sakit/libur), `page`

**POST /api/absensi** (check-in) - Multipart:
```
siswa_id: (implicit from auth)
tanggal: 2026-10-15
status: hadir|izin|sakit|libur
latitude: -7.40123456
longitude: 110.40123456
foto_masuk: (file, required jika status=hadir)
keterangan: (required jika status != hadir)
```
Response 201:
```json
{
  "success": true,
  "message": "Presensi berhasil.",
  "data": {
    "id": 1, "tanggal": "2026-10-15", "status": "hadir",
    "jam_masuk": "07:58:23", "latitude": -7.40..., "longitude": 110.40...,
    "foto_masuk": "/storage/uploads/presensi/2026-10-15/..."
  },
  "next_action": "/jurnal/create"
}
```
**Error:** 422 `ABSENSI_DUPLICATE` (sudah presensi hari ini), 422 `GELOMBANG_NOT_ACTIVE`

**POST /api/absensi/upload-foto** - Multipart:
```
absensi_id: 1
foto_masuk: (file)
```
Untuk retry upload foto jika koneksi lemah (data presensi sudah tersimpan tanpa foto).

**POST /{id}/koreksi** (admin/guru) - Body:
```json
{ "status": "sakit", "keterangan": "Dikoreksi oleh admin", "jam_masuk": "08:30" }
```
Response: absensi updated + `dikoreksi_oleh` terisi.

**GET /api/absensi/rekap** - Query: `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`
Response:
```json
{
  "data": [
    { "siswa": { "id": 1, "nis": "001", "nama": "..." }, "hadir": 45, "izin": 2, "sakit": 1, "libur": 1, "tidak_absen": 1 }
  ]
}
```

**GET /api/absensi/siswa/{id}/periode** - Query: `bulan`, `tahun`
Response: rekap presensi bulanan 1 siswa.

---

## 14. Jurnal Harian (10 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/jurnal | Required | admin, guru, siswa | List jurnal |
| GET | /api/jurnal/{id} | Required | admin, guru, siswa | Detail jurnal |
| POST | /api/jurnal | Required | siswa | Create jurnal |
| PUT | /api/jurnal/{id} | Required | siswa | Edit jurnal (jika editable) |
| DELETE | /api/jurnal/{id} | Required | siswa, admin | Delete jurnal (jika pending) |
| PUT | /api/jurnal/{id}/approve | Required | guru | Approve jurnal |
| PUT | /api/jurnal/{id}/revise | Required | guru | Minta revisi |
| GET | /api/jurnal/rekap | Required | admin, guru | Rekap jurnal |
| GET | /api/jurnal/riwayat | Required | admin, guru, siswa | Riwayat kronologis |
| POST | /api/jurnal/upload-foto | Required | siswa | Upload foto terpisah (retry) |

**GET /api/jurnal** - Query: `siswa_id`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `status_review` (pending/approved/need_revision), `kompetensi_id`, `page`

**POST /api/jurnal** - Body:
```json
{
  "tanggal": "2026-10-15",
  "kompetensi_id": 3,
  "deskripsi": "Membuat konfigurasi router mikrotik untuk jaringan lokal di lab..."
}
```
**Multipart** (jika ada foto): + `foto` (file)

**Logic:** Validasi presensi `hadir` hari ini (code: `ABSENSI_REQUIRED`) → create.

**PUT /{id}** - Logic: siswa owner + `is_editable = true`. Error: `JURNAL_NOT_EDITABLE`

**PUT /{id}/approve** (guru) - Logic: set `status_review = approved`, `is_editable = false`, `reviewed_by = auth id`, `reviewed_at = now()`. Kirim notifikasi ke siswa.

**PUT /{id}/revise** (guru) - Body: `{ "catatan_guru": "Tolong tambahkan detail..." }`
Logic: set `status_review = need_revision`, `is_editable = true`, `reviewed_by = auth id`. Kirim notifikasi ke siswa.

**GET /api/jurnal/riwayat** - Query: `siswa_id`, `tanggal_mulai`, `tanggal_selesai`, `sort` (asc/desc)
Untuk riwayat terurut kronologis per tanggal (pain point #15).

**GET /api/jurnal/rekap** - Query: `gelombang_id`, `siswa_id`, `tanggal_mulai`, `tanggal_selesai`
Response: total jurnal, approved, pending, need_revision per siswa.

---

## 15. Penilaian Bulanan (10 endpoints) 📋

### Aktivasi Penilaian (Admin)

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/aktivasi-penilaian | Required | admin | List periode |
| POST | /api/aktivasi-penilaian | Required | admin | Buka periode |
| PUT | /api/aktivasi-penilaian/{id} | Required | admin | Update periode |
| PUT | /api/aktivasi-penilaian/{id}/close | Required | admin | Tutup periode |
| DELETE | /api/aktivasi-penilaian/{id} | Required | admin | Hapus periode |

**POST** - Body:
```json
{
  "gelombang_id": 1, "bulan": 10, "tahun": 2026,
  "mulai": "2026-10-01", "selesai": "2026-10-10"
}
```

### Penilaian (Guru/Industri via akun siswa)

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/penilaian | Required | admin, guru | List penilaian |
| GET | /api/penilaian/{id} | Required | admin, guru, siswa | Detail penilaian |
| POST | /api/penilaian | Required | guru | Input nilai bulanan |
| PUT | /api/penilaian/{id} | Required | guru | Edit nilai |
| DELETE | /api/penilaian/{id} | Required | admin | Hapus penilaian |
| POST | /api/penilaian/{id}/verifikasi | Required | siswa | Upload foto verifikasi |
| GET | /api/penilaian/nilai-akhir/{siswaId} | Required | admin, guru, siswa | Rekap nilai akhir |

**GET /api/penilaian** - Query: `siswa_id`, `bulan`, `tahun`, `gelombang_id`, `jurusan_id`, `page`

**POST /api/penilaian** (guru) - Body:
```json
{
  "siswa_id": 1,
  "aktivasi_penilaian_id": 1,
  "kompetensi": [
    { "kompetensi_id": 1, "nilai": 85 },
    { "kompetensi_id": 2, "nilai": 90 }
  ],
  "sikap": [
    { "sikap_id": 1, "nilai": 88 },
    { "sikap_id": 2, "nilai": 92 }
  ],
  "catatan": "Kinerja sangat baik"
}
```
**Logic:** Validasi periode `is_active` (error: `PERIODE_CLOSED`) → create `penilaian_bulanan` + bulk insert details → hitung `nilai_akhir` (rata-rata) → kirim notifikasi ke guru pembimbing (`notified_guru_at`).

**POST /{id}/verifikasi** (siswa) - Multipart:
```
foto_verifikasi: (file - swafoto dengan watermark GPS+time)
latitude: -7.xxx (opsional, dari GPS device)
longitude: 110.xxx (opsional)
```
**Logic:** Update `foto_verifikasi`, `verifikasi_lat`, `verifikasi_long`, `verified_at`.

**GET /api/penilaian/nilai-akhir/{siswaId}** - Response:
```json
{
  "siswa": { "id": 1, "nis": "001", "nama": "..." },
  "rata_rata": 87.5,
  "detail_bulanan": [
    { "bulan": 1, "tahun": 2027, "nilai_akhir": 85.0, "verified": true },
    { "bulan": 2, "tahun": 2027, "nilai_akhir": 90.0, "verified": true }
  ]
}
```

---

## 16. Notifications (4 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/notifications | Required | any | List notifikasi user |
| GET | /api/notifications/unread-count | Required | any | Jumlah belum dibaca |
| PUT | /api/notifications/{id}/read | Required | any | Tandai sudah dibaca |
| PUT | /api/notifications/read-all | Required | any | Tandai semua sudah dibaca |

**GET /api/notifications** - Query: `unread_only` (boolean), `page`
Response: Laravel paginated notifications.

**GET /api/notifications/unread-count** - Response: `{ "count": 5 }`

**Notification types:**
- `jurnal-reviewed`: Guru review jurnal siswa
- `pengajuan-approved`: Pengajuan PKL disetujui
- `pengajuan-rejected`: Pengajuan PKL ditolak
- `penilaian-input`: Penilaian bulanan diinput (ke guru)
- `penilaian-verified`: Verifikasi penilaian diterima (ke siswa)
- `absensi-corrected`: Presensi dikoreksi admin (ke siswa)

---

## 17. Dashboard (3 endpoints) 📋

### 17.1 GET /api/dashboard/admin

| | |
|---|---|
| Auth | Required |
| Role | admin |

Response:
```json
{
  "total_siswa": 700,
  "total_guru": 25,
  "total_perusahaan": 40,
  "siswa_by_status": { "pending": 50, "active": 600, "completed": 50 },
  "pengajuan_pending": 12,
  "presensi_hari_ini": { "hadir": 580, "izin": 15, "sakit": 5, "tidak_absen": 100 }
}
```

### 17.2 GET /api/dashboard/guru/{guruId}

| | |
|---|---|
| Auth | Required |
| Role | admin, guru (own) |

Response (ringkasan sekali pandang - pain point #13):
```json
{
  "total_siswa_bimbingan": 20,
  "presensi_hari_ini": { "sudah_absen": 18, "belum_absen": 2 },
  "jurnal_hari_ini": { "sudah_lapor": 15, "belum_lapor": 3, "perlu_review": 5 },
  "siswa_terlambat": [ { "siswa": {...}, "jam_masuk": "08:25" } ],
  "siswa_belum_absen": [ { "siswa": {...} } ]
}
```

### 17.3 GET /api/dashboard/siswa/me

| | |
|---|---|
| Auth | Required |
| Role | siswa |

Response: Status PKL, presensi hari ini, jurnal hari ini, ringkasan nilai.

---

## 18. Rekap Excel (3 endpoints) 📋

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/rekap/excel/presensi | Required | admin, guru | Export presensi |
| GET | /api/rekap/excel/jurnal | Required | admin, guru | Export jurnal |
| GET | /api/rekap/excel/nilai | Required | admin, guru | Export penilaian |

**GET /api/rekap/excel/presensi** - Query: `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`, `siswa_id`
Response: File download (`.xlsx`), Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

**GET /api/rekap/excel/jurnal** - Query: `gelombang_id`, `siswa_id`, `tanggal_mulai`, `tanggal_selesai`

**GET /api/rekap/excel/nilai** - Query: `gelombang_id`, `jurusan_id`, `siswa_id`

---

## Summary

| Module | Endpoints | Status |
|---|---|---|
| 1. Authentication | 5 | 5 ✅ |
| 2. User Management | 5 | 📋 |
| 3. Jurusan | 6 | 📋 |
| 4. Kompetensi | 6 | 📋 |
| 5. Gelombang | 5 | 📋 |
| 6. Perusahaan | 8 | 📋 |
| 7. Sikap | 5 | 📋 |
| 8. Waktu Absen | 4 | 📋 |
| 9. Guru | 7 | 📋 |
| 10. Siswa | 8 | 📋 |
| 11. Pengajuan PKL | 7 (incl. bulk) | 📋 |
| 12. Lokasi PKL | 6 | 📋 |
| 13. Presensi | 9 | 📋 |
| 14. Jurnal Harian | 10 | 📋 |
| 15. Penilaian | 12 | 📋 |
| 16. Notifications | 4 | 📋 |
| 17. Dashboard | 3 | 📋 |
| 18. Rekap Excel | 3 | 📋 |
| **Total** | **~118** | **5 done, ~113 planned** |
