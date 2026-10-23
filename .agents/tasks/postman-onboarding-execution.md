# Playbook Eksekusi Postman — Onboarding Profil Siswa (Sprint 2)

Dokumen kerja untuk **menambahkan request onboarding siswa ke collection Postman** (GUI) setelah endpoint Sprint 2 tersedia. Isi di sini siap-tempel: headers, body, *Tests*, tab *Documentation*, dan *Examples*.

Panduan konsep/umum: `.agents/tech-specs/2026/004-postman-collection-guide.md`.
Playbook autentikasi: `.agents/tasks/postman-auth-execution.md`.

**Ruang lingkup:** 3 endpoint onboarding di folder `10. Data - Siswa`
`GET /api/siswa/me`, `PUT /api/siswa/me/profil`, `PUT /api/siswa/me/konfirmasi` + 4 skenario di folder `99. Test Scenarios`.

---

## 0. Prasyarat

1. Branch `feature/siswa-onboarding` sudah merged ke `develop` dan backend jalan:
   ```sh
   php artisan serve
   php artisan migrate:fresh --seed
   ```
2. Environment `SIMAKIN Local` sudah berisi `base_url`, `token`, `siswa_nis`, dan `siswa_password` (lihat `postman-auth-execution.md` §1). Tambahkan:
   | Variable | Initial Value | Keterangan |
   |---|---|---|
   | `siswa_nis` | `0012345` | NIS siswa uji (username login) |
   | `siswa_baru_password` | `password123` | Password default siswa hasil create/import |
3. Ada siswa **belum konfirmasi**. Pilih salah satu:
   - Seeder Sprint 2 menyediakan 2 siswa dengan `profil_confirmed_at = null` (cek `GET /api/siswa` sebagai admin), **atau**
   - Buat via `POST /api/siswa` (admin):
     ```json
     {
       "name": "Siswa Uji Onboarding",
       "nis": "0012345",
       "password": "password123",
       "password_confirmation": "password123"
     }
     ```
     Field profil sengaja tidak dikirim agar `NULL`.
4. Login siswa untuk mendapatkan `token`:
   - Jalankan `POST Login (Mobile)` dengan body `{ "username": "{{siswa_nis}}", "password": "{{siswa_baru_password}}" }`.
   - Token tersimpan otomatis oleh *Tests* request login.

> **Route order:** `/api/siswa/me*` harus terdaftar sebelum `/api/siswa/{id}`. Jika `GET /api/siswa/me` mengembalikan 404 “No query results”, periksa urutan route (`php artisan route:list --path=api/siswa`).

---

## 1. Folder `10. Data - Siswa`

Urutan request (setelah request CRUD yang sudah ada):

| # | Request | Method |
|---|---|---|
| 10.7 | `GET Saya (Me)` | GET |
| 10.8 | `PUT Update Profil Saya` | PUT |
| 10.9 | `PUT Konfirmasi Profil` | PUT |

> `GET Lokasi PKL Siswa` tetap ada di tree tetapi **baru aktif Sprint 3** (butuh tabel `lokasi_pkl`).

### 1.1 `GET Saya (Me)`

- **Method/URL:** `GET {{base_url}}/siswa/me`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Mengembalikan data profil siswa", function () {
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("user");
      pm.expect(json.data).to.have.property("siswa");
      pm.expect(json.data).to.have.property("profil_terkonfirmasi");
      pm.expect(json.data).to.have.property("profil_lengkap");
  });

  pm.test("NIS read-only sesuai username", function () {
      pm.expect(json.data.siswa.nis).to.eql(json.data.user.username);
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Mengambil profil siswa yang sedang login + status onboarding.

  ## Response
  - `data.user`: `id`, `name`, `username` (= NIS), `email`
  - `data.siswa`: `nis`, `jenis_kelamin`, `kelas`, `telp`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`
  - `data.akademik`: `jurusan`, `gelombang` (memuat `tahun_ajaran`)
  - `data.profil_confirmed_at`, `data.profil_terkonfirmasi`, `data.profil_lengkap`

  ## Error
  - 401 token tidak valid
  - 403 bukan role siswa
  ```
- **Example `200`:** lihat `docs/API-Docs-fondasi.md` §G.1.

### 1.2 `PUT Update Profil Saya`

- **Method/URL:** `PUT {{base_url}}/siswa/me/profil`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  Content-Type: application/json
  ```
- **Body** (raw → JSON) — partial, sesuaikan ID jurusan/gelombang hasil seeder:
  ```json
  {
    "name": "Siswa Uji Onboarding",
    "jenis_kelamin": "L",
    "telp": "081234567890",
    "email": "siswa.uji@example.com",
    "alamat": "Jl. Contoh No. 1, Magelang",
    "kelas": "XII TJKT 1",
    "jurusan_id": 7,
    "gelombang_id": 1,
    "nama_ortu": "Budi Santoso",
    "telp_ortu": "081234567891",
    "alamat_ortu": "Jl. Contoh No. 1, Magelang"
  }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Profil tersimpan", function () {
      pm.expect(json.message).to.include("Profil");
      pm.expect(json.data.siswa.alamat).to.eql("Jl. Contoh No. 1, Magelang");
      pm.expect(json.data.user.email).to.eql("siswa.uji@example.com");
  });

  pm.test("NIS tidak berubah", function () {
      pm.expect(json.data.siswa.nis).to.eql(pm.environment.get("siswa_nis"));
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Melengkapi/mengubah profil saat onboarding. Hanya boleh sebelum profil dikonfirmasi.

  ## Body (semua opsional)
  `name`, `jenis_kelamin` (L/P), `telp`, `email`, `alamat`, `kelas`, `jurusan_id`, `gelombang_id`, `nama_ortu`, `telp_ortu`, `alamat_ortu`.

  ## Aturan
  - `name` → `users.name`, `email` → `users.email` (unik)
  - `nis`/`username` tidak boleh dikirim (read-only)
  - Setelah konfirmasi → 422 `PROFIL_SUDAH_DIKONFIRMASI`

  ## Error
  - 401 token tidak valid
  - 403 bukan role siswa
  - 422 validasi gagal / profil sudah dikonfirmasi
  ```

### 1.3 `PUT Konfirmasi Profil`

- **Method/URL:** `PUT {{base_url}}/siswa/me/konfirmasi`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  ```
- **Body:** _(kosong)_
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Profil terkonfirmasi", function () {
      pm.expect(json.data.profil_terkonfirmasi).to.eql(true);
      pm.expect(json.data.profil_confirmed_at).to.not.be.null;
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Mengonfirmasi profil siswa. Validasi seluruh field wajib sudah terisi, lalu set `profil_confirmed_at`.

  ## Field wajib
  `name`, `jenis_kelamin`, `telp`, `email`, `alamat`, `kelas`, `jurusan_id`, `gelombang_id`, `nama_ortu`, `telp_ortu`, `alamat_ortu`.

  ## Response
  - 200: `{ message, data }` dengan `profil_terkonfirmasi = true`
  - 422 `PROFIL_BELUM_LENGKAP` + `errors` bila ada field kosong
  - 200 idempotent: `{ "message": "Profil sudah dikonfirmasi sebelumnya." }`

  ## Setelah konfirmasi
  Siswa tidak dapat mengubah profil; perubahan hanya oleh admin. Siswa tetap dapat mengubah kata sandi.
  ```

---

## 2. Folder `99. Test Scenarios`

Tambahkan request berikut (prefix `[Onboarding]`). Gunakan siswa **belum konfirmasi**; jika sudah konfirmasi, buat siswa baru via admin atau `migrate:fresh --seed`.

### 2.1 `[Onboarding] GET Saya (Me) - Belum Konfirmasi`

- **Method/URL:** `GET {{base_url}}/siswa/me`
- **Headers:** `Accept: application/json`, `Authorization: Bearer {{token}}` (token siswa belum konfirmasi)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const data = pm.response.json().data;

  pm.test("Profil belum terkonfirmasi", function () {
      pm.expect(data.profil_terkonfirmasi).to.eql(false);
      pm.expect(data.profil_confirmed_at).to.be.null;
  });

  pm.test("Profil belum lengkap", function () {
      pm.expect(data.profil_lengkap).to.eql(false);
  });
  ```

### 2.2 `[Onboarding] PUT Konfirmasi Profil - Belum Lengkap`

- **Method/URL:** `PUT {{base_url}}/siswa/me/konfirmasi`
- **Headers:** `Accept: application/json`, `Authorization: Bearer {{token}}`
- **Prasyarat:** profil sengaja dikosongkan (siswa baru) atau minimal satu field wajib belum diisi.
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });

  const json = pm.response.json();

  pm.test("Kode PROFIL_BELUM_LENGKAP", function () {
      pm.expect(json.code).to.eql("PROFIL_BELUM_LENGKAP");
  });

  pm.test("Menampilkan field yang kurang", function () {
      pm.expect(json).to.have.property("errors");
      pm.expect(Object.keys(json.errors).length).to.be.above(0);
  });
  ```

### 2.3 `[Onboarding] PUT Update Profil - Setelah Konfirmasi`

- **Method/URL:** `PUT {{base_url}}/siswa/me/profil`
- **Headers:** `Accept: application/json`, `Authorization: Bearer {{token}}`
- **Body:**
  ```json
  { "alamat": "Alamat baru" }
  ```
- **Prasyarat:** profil sudah dikonfirmasi (jalankan 2.4/flow dulu).
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });

  pm.test("Kode PROFIL_SUDAH_DIKONFIRMASI", function () {
      pm.expect(pm.response.json().code).to.eql("PROFIL_SUDAH_DIKONFIRMASI");
  });
  ```

### 2.4 `[Flow] Onboarding Siswa Flow`

Jalankan berurutan via **Collection Runner**:

1. `POST Login (Mobile)` → `username` = `{{siswa_nis}}`, password default siswa baru
2. `GET Saya (Me)` → assert `profil_terkonfirmasi = false`
3. `PUT Update Profil Saya` → kirim semua field wajib
4. `PUT Konfirmasi Profil` → assert `profil_terkonfirmasi = true`
5. `GET Saya (Me)` → assert `profil_lengkap = true` dan `profil_confirmed_at` terisi
6. `PUT Update Profil Saya` → assert 422 `PROFIL_SUDAH_DIKONFIRMASI`
7. `GET Get User` → assert data user tetap valid
8. `POST Logout` → cleanup token

> Untuk mengulang flow, reset data: `php artisan migrate:fresh --seed` atau buat siswa baru dengan NIS berbeda.

### 2.5 Verifikasi Admin Tetap Bisa Mengubah

- Login admin (web atau mobile).
- `PUT /api/siswa/{id}` dengan body `{ "alamat": "Alamat hasil validasi sekolah" }`.
- **Tests:** status 200; `data.alamat` berubah; `profil_confirmed_at` **tidak** berubah/reset.
- Jalankan `GET /api/siswa/me` dengan token siswa → alamat baru terlihat, `profil_terkonfirmasi` tetap `true`.

---

## 3. Jalankan Collection Runner

1. Buka collection → **Run**.
2. Pilih folder `10. Data - Siswa` (request 10.7–10.9) atau folder `99` untuk skenario.
3. Pilih environment `SIMAKIN Local`; iteration 1; delay 100ms.
4. Pastikan urutan: login → GET me → update → konfirmasi → cek error.

---

## 4. Troubleshooting

| Gejala | Penyebab / Solusi |
|---|---|
| `401 Unauthenticated` | Token kosong/kedaluwarsa → jalankan `POST Login (Mobile)` |
| `403 Forbidden` | Token bukan milik siswa (admin/guru) → login akun siswa |
| `404 No query results for model [Siswa]` di `/siswa/me` | Route `/siswa/{id}` terdaftar sebelum `/siswa/me` → perbaiki urutan route |
| `422` saat update profil | Cek `errors`; email mungkin sudah dipakai user lain |
| `422 PROFIL_SUDAH_DIKONFIRMASI` | Profil sudah dikonfirmasi → reset data atau pakai siswa baru |
| `422 PROFIL_BELUM_LENGKAP` | Lengkapi semua field wajib sebelum konfirmasi |
| Konfirmasi sukses tapi `profil_lengkap` false | Cek `users.email`/`users.name`; completeness membaca relasi `user` |

---

## 5. Checklist (Definition of Done)

- [ ] Request `GET Saya (Me)` diperbarui (response onboarding)
- [ ] Request `PUT Update Profil Saya` ditambahkan + tests + documentation
- [ ] Request `PUT Konfirmasi Profil` ditambahkan + tests + documentation
- [ ] 4 skenario `[Onboarding]` + `[Flow] Onboarding Siswa Flow` ditambahkan di folder 99
- [ ] Environment `SIMAKIN Local` memiliki `siswa_nis` & `siswa_baru_password`
- [ ] Flow onboarding lulus; error lengkap & terkunci terverifikasi
- [ ] Admin tetap bisa mengubah profil setelah konfirmasi
- [ ] Collection diexport ulang ke `docs/postman/SIMAKIN-API-Collection.json`
