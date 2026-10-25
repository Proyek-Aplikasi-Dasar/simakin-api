# API Docs untuk Fondasi

## Informasi Umum

| Item | Nilai |
|---|---|
| Variabel `base_url` | `http://localhost:8000/api` |
| URL CSRF cookie (khusus web) | `http://localhost:8000/sanctum/csrf-cookie` |
| Format data | JSON |

**role_id:** 1 (admin), 2 (guru), 3 (siswa)

**Identitas login: `username` (bukan email).** Aturan username: siswa = NIS, guru = NIP, admin = username kustom.

**Header untuk semua request:**

```
Accept: application/json
X-Client: mobile
```

> `X-Client: mobile` hanya dikirim oleh klien mobile. Request web tidak mengirim header ini (lihat bagian B).

---

## A. Mobile

### 1. Login

#### a. Login biasa dengan akun yang tersedia

```
POST {{base_url}}/login
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Body (raw, JSON):**

```json
{
  "username": "siswa",
  "password": "siswa123"
}
```

> Untuk akun siswa, `username` = **NIS** (contoh: `0012345`). Akun demo `siswa/siswa123` tetap tersedia; siswa hasil import/bulk upload login memakai NIS masing-masing.

**Output:** `200 OK`

```json
{
  "user": {
    "id": 4,
    "name": "Siswa Teladan",
    "username": "siswa",
    "role_id": 3,
    "created_at": "2026-09-30T04:02:08.000000Z",
    "updated_at": "2026-09-30T04:02:08.000000Z"
  },
  "token": "<token-hasil-login>"
}
```

### 2. Ambil data user yang sudah login

#### a. Dengan token dari hasil login sebelumnya

```
GET {{base_url}}/user
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Authorization:** Bearer Token → `{{token}}` (didapat dari hasil `POST /login` sebelumnya)

**Output:** `200 OK`

```json
{
  "id": 4,
  "name": "Siswa Teladan",
  "username": "siswa",
  "role_id": 3,
  "created_at": "2026-09-30T04:02:08.000000Z",
  "updated_at": "2026-09-30T04:02:08.000000Z"
}
```

#### b. Tanpa token

**Output:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

### 3. Logout

#### a. Logout

```
POST {{base_url}}/logout
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Authorization:** Bearer Token → `{{token}}` (didapat dari hasil `POST /login` sebelumnya)

**Output:** `200 OK`

```json
{
  "message": "Berhasil logout."
}
```

#### b. Ambil data user yang sudah logout

```
GET {{base_url}}/user
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Authorization:** Bearer Token → `{{token}}` (didapat dari hasil `POST /login` sebelumnya)

**Output:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

---

## B. Web

### 1. Mengambil token CSRF

```
GET http://localhost:8000/sanctum/csrf-cookie
```

**Header:** `Accept: application/json`

**Output:** `204 No Content`

Di tab Cookies terdapat `XSRF-TOKEN` dengan value berupa string base64 yang diakhiri `%3D`, misalnya:

```
eyJpdiI6Ii...<dipotong>...dGFnIjoiIn0%3D
```

### 2. Login dengan token CSRF

```
POST {{base_url}}/login
```

**Headers:**

```
Accept: application/json
Origin: http://localhost:5173
X-XSRF-TOKEN: <nilai cookie XSRF-TOKEN yang di-decode>
```

> Cara decode: tanda `%3D` di akhir diganti dengan `=`.

**Body:**

```json
{
  "username": "guru",
  "password": "guru123"
}
```

**Output:** `200 OK`

```json
{
  "user": {
    "id": 3,
    "name": "Guru Pengajar",
    "username": "guru",
    "role_id": 2,
    "created_at": "2026-09-30T04:02:08.000000Z",
    "updated_at": "2026-09-30T04:02:08.000000Z"
  }
}
```

**Cookies:** server menyetel cookie session (nilai base64 berakhiran `%3D`) yang dikirim otomatis pada request berikutnya.

### 3. Mengambil data user yang login (tanpa Authorization)

```
GET {{base_url}}/user
```

**Headers:**

```
Accept: application/json
Origin: http://localhost:5173
```

**Output:** `200 OK`

```json
{
  "id": 3,
  "name": "Guru Pengajar",
  "username": "guru",
  "role_id": 2,
  "created_at": "2026-09-30T04:02:08.000000Z",
  "updated_at": "2026-09-30T04:02:08.000000Z"
}
```

**Cookies:** nilai cookie `XSRF-TOKEN` diperbarui oleh server pada response ini.

### 4. Logout user, kemudian ambil data user

#### a. Logout

```
POST {{base_url}}/logout
```

**Headers:**

```
Accept: application/json
Origin: http://localhost:5173
X-XSRF-TOKEN: <nilai cookie XSRF-TOKEN dari langkah 3, di-decode>
```

> Cara decode: tanda `%3D` di akhir diganti dengan `=`.

**Output:** `200 OK`

```json
{
  "message": "Berhasil logout."
}
```

#### b. Ambil data user

```
GET {{base_url}}/user
```

**Headers:**

```
Accept: application/json
Origin: http://localhost:5173
```

**Output:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

---

## C. Registrasi (Web)

> Hanya admin yang dapat mendaftarkan pengguna baru. Akun siswa dibuat melalui menu data siswa.

### 1. Registrasi pengguna baru

```
POST {{base_url}}/register
```

**Headers:**

```
Accept: application/json
Origin: http://localhost:5173
```

**Authorization:** Session/Bearer admin yang sudah login.

**Body (raw, JSON):**

```json
{
  "name": "Guru Baru",
  "username": "guru.baru",
  "password": "password123",
  "password_confirmation": "password123",
  "role_id": 2
}
```

> `username` wajib unik (guru = NIP, admin = username kustom). `role_id` hanya boleh `1` (admin) atau `2` (guru). Password minimal 8 karakter.

**Output:** `201 Created`

```json
{
  "message": "Pengguna berhasil didaftarkan.",
  "data": {
    "id": 5,
    "name": "Guru Baru",
    "username": "guru.baru",
    "role_id": 2,
    "role": { "id": 2, "nama": "guru" },
    "created_at": "2026-10-16T02:00:00.000000Z",
    "updated_at": "2026-10-16T02:00:00.000000Z"
  }
}
```

**Kemungkinan error:**

| Status | Kondisi |
|---|---|
| `401` | Belum login |
| `403` | Bukan admin |
| `422` | Username duplikat, `role_id` bukan admin/guru, atau password tidak valid |

---

## D. Ubah Kata Sandi

> Berlaku untuk web (admin/guru) dan mobile (siswa). Setelah berhasil, semua token mobile lain dan sesi web lain dicabut (kecuali sesi saat ini).

### 1. Ubah kata sandi

```
PUT {{base_url}}/user/password
```

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}   (mobile)
```

**Body (raw, JSON):**

```json
{
  "current_password": "lama12345",
  "password": "baru12345",
  "password_confirmation": "baru12345"
}
```

> Password baru minimal 8 karakter dan berbeda dari kata sandi saat ini.

**Output:** `200 OK`

```json
{
  "message": "Kata sandi berhasil diubah."
}
```

**Kemungkinan error:**

| Status | Kondisi |
|---|---|
| `401` | Belum login |
| `422` | Kata sandi saat ini salah, konfirmasi tidak cocok, atau kurang dari 8 karakter |

---

## E. Tes Kasus Gagal

### 1. Login dengan password yang salah

```
POST {{base_url}}/login   (Mobile)
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Body:**

```json
{
  "username": "siswa",
  "password": "asikinaja"
}
```

**Output:** `422 Unprocessable Entity`

```json
{
  "message": "Kredensial tidak valid.",
  "errors": {
    "username": [
      "Kredensial tidak valid."
    ]
  }
}
```

### 2. Login dengan username tidak terdaftar

```
POST {{base_url}}/login   (Mobile)
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Body:**

```json
{
  "username": "siswateladan",
  "password": "siswa123"
}
```

**Output:** `422 Unprocessable Entity`

```json
{
  "message": "Kredensial tidak valid.",
  "errors": {
    "username": [
      "Kredensial tidak valid."
    ]
  }
}
```

### 3. Login dengan field kosong

```
POST {{base_url}}/login   (Mobile)
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

**Body:**

```json
{}
```

**Output:** `422 Unprocessable Entity`

```json
{
  "message": "The username field is required. (and 1 more error)",
  "errors": {
    "username": [
      "The username field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

### 4. Akses tanpa login

```
GET {{base_url}}/user   (Mobile)
```

**Headers:**

```
Accept: application/json
X-Client: mobile
```

Abaikan Authorization. **Body:** `{}`

**Output:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

---

## F. Data Master

32 endpoint data master sudah tersedia. Semua endpoint memerlukan login (`auth:sanctum`):

- **Baca** (`GET`): semua role (admin, guru, siswa)
- **Tulis** (`POST`, `PUT`, `DELETE`): hanya **admin** (guru/siswa → `403`)

Header mobile:

```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

### 1. Ringkasan Endpoint

| Resource | Method & Endpoint | Akses |
|---|---|---|
| Jurusan | GET `/jurusan`, GET `/jurusan/{id}`, GET `/jurusan/{id}/kompetensi` | semua role |
| | POST `/jurusan`, PUT `/jurusan/{id}`, DELETE `/jurusan/{id}` | admin |
| Kompetensi | GET `/kompetensi`, GET `/kompetensi/{id}` | semua role |
| | POST `/kompetensi`, PUT `/kompetensi/{id}`, DELETE `/kompetensi/{id}` | admin |
| Gelombang | GET `/gelombang`, GET `/gelombang/{id}` | semua role |
| | POST `/gelombang`, PUT `/gelombang/{id}`, DELETE `/gelombang/{id}` | admin |
| Perusahaan | GET `/perusahaan`, GET `/perusahaan/{id}` | semua role |
| | POST `/perusahaan`, PUT `/perusahaan/{id}`, DELETE `/perusahaan/{id}` | admin |
| | POST `/perusahaan/{id}/jurusan`, DELETE `/perusahaan/{id}/jurusan/{jurusanId}` | admin |
| Sikap | GET `/sikap`, GET `/sikap/{id}` | semua role |
| | POST `/sikap`, PUT `/sikap/{id}`, DELETE `/sikap/{id}` | admin |
| Waktu Absen | GET `/waktu-absen` | semua role |
| | POST `/waktu-absen`, PUT `/waktu-absen/{id}`, DELETE `/waktu-absen/{id}` | admin |

**Catatan:** waktu absen tidak punya endpoint detail (`GET /waktu-absen/{id}`). Bulk upload kompetensi/perusahaan belum tersedia (Sprint 2).

### 2. Format Respons Standar

**List (paginated):** `GET {{base_url}}/jurusan`

```json
{
  "data": [
    { "id": 7, "nama": "Teknik Otomotif", "kode": "TKR" }
  ],
  "links": {
    "first": "http://localhost:8000/api/jurusan?page=1",
    "last": "http://localhost:8000/api/jurusan?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 10
  }
}
```

**Detail:** `{ "data": { ... } }`

**Create:** `201 Created`

```json
{
  "message": "Jurusan berhasil disimpan.",
  "data": { "id": 11, "nama": "Teknik Otomotif", "kode": "TKR" }
}
```

**Update:** `200 OK` → `{ "message": "Jurusan berhasil diperbarui.", "data": { ... } }`

**Delete:** `200 OK` → `{ "message": "Jurusan berhasil dihapus." }`

**Validasi gagal:** `422 Unprocessable Entity`

```json
{
  "message": "The nama field is required.",
  "errors": {
    "nama": ["The nama field is required."]
  }
}
```

### 3. Jurusan (6 endpoint)

**GET** `/jurusan` - query: `search` (nama/kode)

**GET** `/jurusan/{id}/kompetensi` - daftar kompetensi milik jurusan

**POST** `/jurusan` - body:

```json
{ "nama": "Teknik Otomotif", "kode": "TKR" }
```

**PUT** `/jurusan/{id}` - body sama seperti POST.

**DELETE** `/jurusan/{id}` - kompetensi terkait ikut terhapus (`CASCADE`).

### 4. Kompetensi (5 endpoint)

**GET** `/kompetensi` - query: `jurusan_id`, `search` (nama/kode)

**POST** `/kompetensi` - body:

```json
{ "jurusan_id": 7, "nama": "Perawatan Mesin Kendaraan", "kode": "PMK" }
```

### 5. Gelombang (5 endpoint)

**GET** `/gelombang` - query: `search` (nama/tahun ajaran)

**POST** `/gelombang` - body:

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

**Validasi:** `daftar_mulai <= daftar_selesai` dan `praktik_mulai <= praktik_selesai`.

### 6. Perusahaan (7 endpoint)

**GET** `/perusahaan` - query: `jurusan_id`, `search` (nama/alamat). Filter `kuota_tersedia` menyusul Sprint 3.

**POST** `/perusahaan` - body:

```json
{
  "nama": "PT Contoh Industri",
  "alamat": "Jl. Contoh No. 1, Magelang",
  "latitude": -7.4698,
  "longitude": 110.2177,
  "pimpinan": "Budi Santoso",
  "telp": "081234567890",
  "email": "hrd@contoh.co.id",
  "deskripsi": "Perusahaan manufaktur",
  "kuota": 10,
  "jurusan_ids": [7, 6]
}
```

**POST** `/perusahaan/{id}/jurusan` - body: `{ "jurusan_id": 7 }` → `{ "message": "Jurusan berhasil ditambahkan ke perusahaan.", "data": { ... } }`

**DELETE** `/perusahaan/{id}/jurusan/{jurusanId}` → `{ "message": "Jurusan berhasil dilepas dari perusahaan." }` (404 jika relasi tidak ada)

### 7. Sikap (5 endpoint)

**GET** `/sikap` - query: `search` (nama)

**POST** `/sikap` - body:

```json
{ "nama": "Disiplin", "deskripsi": "Tepat waktu hadir dan menyelesaikan tugas." }
```

### 8. Waktu Absen (4 endpoint)

**GET** `/waktu-absen` - tanpa endpoint detail

**POST** `/waktu-absen` - body:

```json
{ "mulai": "06:00", "selesai": "22:00", "keterangan": "Jam kerja normal" }
```

**Validasi:** `mulai`/`selesai` format `H:i`, `selesai` harus lebih besar dari `mulai`.

**Response resource:** `{ "id": 1, "mulai": "06:00", "selesai": "22:00", "keterangan": "..." }`

### 9. Error Umum

| Status | Kapan |
|---|---|
| `401` | Belum login / token tidak valid |
| `403` | Guru atau siswa mencoba menulis data |
| `404` | ID tidak ditemukan (atau relasi pivot tidak ada) |
| `422` | Validasi gagal |

### 10. Data Seeder & Fondasi Import

`php artisan migrate:fresh --seed` mengisi:

| Tabel | Jumlah |
|---|---|
| `jurusan` | 10 |
| `kompetensi` | 5 per jurusan |
| `gelombang` | 2 |
| `perusahaan` | 12 (+ relasi jurusan) |
| `sikap` | 10 |
| `waktu_absen` | 1 (06:00–22:00) |

Fondasi import Excel (`maatwebsite/excel` v4 + `app/Imports/Concerns/ImportsRows`) sudah tersedia dan teruji, tetapi endpoint bulk upload baru dibuka di Sprint 2. Format respons import nanti:

```json
{
  "message": "Import berhasil: 15 data. Gagal: 2 data.",
  "success": 15,
  "failed": 2,
  "errors": [
    { "row": 3, "error": "Nama jurusan wajib diisi." }
  ]
}
```

---

## G. Onboarding Profil Siswa (Mobile)

**Prasyarat:** sudah login mobile (`X-Client: mobile`). Semua request memakai header:

```
Accept: application/json
Authorization: Bearer {token}
```

**Identitas:** siswa login dengan `username` = **NIS**. NIS di onboarding bersifat **read-only** (diambil dari `users.username`).

**Data yang dilengkapi:** data diri (nama lengkap, NIS, jenis kelamin, no HP/WA, email aktif, alamat domisili), akademik (kelas, jurusan, tahun ajaran dari gelombang), dan orang tua/wali (nama, no HP/WA, alamat).

### 1. Ambil Profil Sendiri

```
GET {{base_url}}/siswa/me
```

**Output:** `200 OK`

```json
{
  "data": {
    "user": {
      "id": 4,
      "name": "Siswa Teladan",
      "username": "0012345",
      "email": null
    },
    "siswa": {
      "id": 1,
      "nis": "0012345",
      "jenis_kelamin": null,
      "kelas": null,
      "telp": null,
      "alamat": null,
      "nama_ortu": null,
      "telp_ortu": null,
      "alamat_ortu": null
    },
    "akademik": {
      "jurusan": null,
      "gelombang": null
    },
    "profil_confirmed_at": null,
    "profil_terkonfirmasi": false,
    "profil_lengkap": false
  }
}
```

> `akademik.gelombang` memuat `tahun_ajaran` (tidak ada kolom tahun ajaran terpisah). `profil_lengkap` bernilai `true` jika seluruh field wajib konfirmasi sudah terisi.

### 2. Lengkapi / Ubah Profil

```
PUT {{base_url}}/siswa/me/profil
```

**Body (raw, JSON)** — partial, semua field opsional:

```json
{
  "name": "Siswa Teladan",
  "jenis_kelamin": "L",
  "telp": "081234567890",
  "email": "siswa@example.com",
  "alamat": "Jl. Contoh No. 1, Magelang",
  "kelas": "XII TJKT 1",
  "jurusan_id": 7,
  "gelombang_id": 1,
  "nama_ortu": "Budi Santoso",
  "telp_ortu": "081234567891",
  "alamat_ortu": "Jl. Contoh No. 1, Magelang"
}
```

**Output:** `200 OK`

```json
{
  "message": "Profil berhasil diperbarui.",
  "data": { "...": "sama seperti GET /siswa/me" }
}
```

**Aturan:**

- `name` disimpan ke `users.name`; `email` ke `users.email` (unik, bukan identitas login).
- `nis` dan `username` **tidak diterima** dari siswa.
- Hanya bisa dilakukan sebelum profil dikonfirmasi. Setelah konfirmasi → `422 PROFIL_SUDAH_DIKONFIRMASI` (hubungi admin).

### 3. Konfirmasi Profil

```
PUT {{base_url}}/siswa/me/konfirmasi
```

Tanpa body. Validasi kelengkapan: `name`, `jenis_kelamin`, `telp`, `email`, `alamat`, `kelas`, `jurusan_id`, `gelombang_id`, `nama_ortu`, `telp_ortu`, `alamat_ortu`.

**Output sukses:** `200 OK`

```json
{
  "message": "Profil berhasil dikonfirmasi.",
  "data": { "...": "profil dengan profil_terkonfirmasi = true" }
}
```

**Belum lengkap:** `422 Unprocessable Entity`

```json
{
  "message": "Profil belum lengkap.",
  "code": "PROFIL_BELUM_LENGKAP",
  "errors": {
    "email": ["Email wajib diisi."],
    "alamat": ["Alamat wajib diisi."]
  }
}
```

**Sudah pernah dikonfirmasi:** `200 OK` → `{ "message": "Profil sudah dikonfirmasi sebelumnya." }`

### 4. Error Onboarding

| Code | HTTP | Kapan |
|---|---|---|
| `PROFIL_BELUM_KONFIRMASI` | `403` | Mengakses endpoint transaksional sebelum konfirmasi (Sprint 3+) |
| `PROFIL_BELUM_LENGKAP` | `422` | Konfirmasi saat masih ada field wajib kosong |
| `PROFIL_SUDAH_DIKONFIRMASI` | `422` | Siswa mengubah profil setelah konfirmasi |

### 5. Template Excel Bulk Upload

Admin dapat mengunduh template siap pakai untuk import data via `GET /api/templates/{type}` (auth: `admin`, header `Authorization: Bearer {token}` atau session).

| Tipe | File | Kolom | Keterangan |
|---|---|---|---|
| `guru` | `template-guru.xlsx` | `nip`, `nama` | Wajib diisi. `nip` dinormalisasi (hindari notasi ilmiah), jadi username/login. |
| `siswa` | `template-siswa.xlsx` | `nis`, `nama`, `jenis_kelamin`, `kelas`, `jurusan`, `gelombang`, `telp`, `email`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu` | `nis` & `nama` wajib. `jenis_kelamin` `L`/`P` atau `Laki-laki`/`Perempuan`. `jurusan` bisa kode/nama (case-insensitive). `gelombang` berdasarkan nama. Kolom profil boleh kosong → diisi saat onboarding. |
| `perusahaan` | `template-perusahaan.xlsx` | `nama`, `alamat`, `latitude`, `longitude`, `pimpinan`, `telp`, `email`, `deskripsi`, `kuota`, `jurusan_kode` | Ikuti aturan import perusahaan. |
| `kompetensi` | `template-kompetensi.xlsx` | `jurusan_kode`, `nama`, `kode` | `jurusan_kode` wajib merujuk jurusan valid. |

> Password default hasil import: `password123` (guru & siswa).

### 6. Alur Aplikasi Mobile

```
Login (username = NIS)
  → GET /siswa/me
  → profil_terkonfirmasi == false ? tampilkan onboarding
      → PUT /siswa/me/profil (isi bertahap)
      → PUT /siswa/me/konfirmasi
  → masuk ke halaman utama
```

Setelah konfirmasi, siswa **tidak bisa** mengubah data diri/akademik/orang tua/wali. Perubahan hanya oleh admin (validasi sekolah); siswa tetap bisa mengubah kata sandi lewat `PUT /api/user/password`.