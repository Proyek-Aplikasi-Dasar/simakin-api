# Playbook Eksekusi Postman — Sprint 2 (Guru, Siswa, Onboarding, User Management & Template Excel)

Dokumen kerja untuk **menambahkan folder 02, 09, 10, 19 ke collection `SIMAKIN API`** (GUI Postman) bagi endpoint Sprint 2. Isi di sini sudah siap-tempel: headers, body, *Pre-request Script*, *Tests*, tab *Documentation*, dan *Examples*.

**Panduan konsep/umum:** `.agents/tech-specs/2026/004-postman-collection-guide.md`  
**Pola autentikasi (folder 01):** `.agents/tasks/postman-auth-execution.md`  
**Playbook Data Master (Sprint 1):** `.agents/tasks/postman-master-data-execution.md`

**Ruang lingkup:** Folder `02. User Management`, `09. Data - Guru`, `10. Data - Siswa`, `19. Template Excel` + skenario di folder `99. Test Scenarios`.

| Folder | Requests | Keterangan |
|---|---|---|
| 02. User Management | 5 | List/detail/update/reset password/soft delete (admin only) |
| 09. Data - Guru | 7 | CRUD + bulk upload + placeholder siswa bimbingan (Sprint 3) |
| 10. Data - Siswa | 10 | CRUD + bulk upload + onboarding me/profil/konfirmasi + placeholder lokasi PKL (Sprint 3) |
| 19. Template Excel | 4 | Download template guru/siswa/perusahaan/kompetensi (admin only) |

> **Keputusan auth:** CRUD admin-only memakai **Bearer token admin** (`{{admin_token}}`). Onboarding `/siswa/me*` memakai **Bearer token siswa** (`{{siswa_token}}`). Login via header `X-Client: mobile` mengembalikan token untuk role apa pun.

---

## 0. Prasyarat

1. Folder `01. Authentication` collection `SIMAKIN API` sudah selesai (lihat `postman-auth-execution.md`).
2. Backend jalan + database ter-seed:
   ```sh
   php artisan serve
   php artisan migrate --seed
   ```
3. Seeder minimal berisi role, user admin/guru/siswa, jurusan, gelombang, serta data hasil Sprint 2 (guru/siswa sample).
4. Environment `SIMAKIN Local` aktif.

### 0.1 Tambah variables environment

Tambahkan variabel berikut di `SIMAKIN Local`:

| Variable | Initial Value | Keterangan |
|---|---|---|
| `admin_token` | _(kosong)_ | Diisi otomatis saat login admin/mobile |
| `guru_token` | _(kosong)_ | Diisi otomatis saat login guru (untuk skenario izin) |
| `siswa_token` | _(kosong)_ | Diisi otomatis saat login siswa (untuk onboarding) |
| `user_id` | _(kosong)_ | ID user untuk test User Management |
| `guru_id` | _(kosong)_ | ID guru untuk test CRUD guru |
| `siswa_id` | _(kosong)_ | ID siswa untuk test CRUD siswa |
| `admin_user_id` | _(kosong)_ | ID user admin untuk skenario `CANNOT_DELETE_SELF` (bisa diisi manual dari `/users`) |
| `siswa_nis` | `0012345` | NIS siswa uji (dipakai sebagai `username` login) |
| `siswa_baru_password` | `password123` | Password default siswa hasil create/import |

### 0.2 Standar headers

- **Untuk request admin-only (02, 09 admin-write, 10 admin-write, 19):**
  ```text
  Accept: application/json
  Authorization: Bearer {{admin_token}}
  ```

- **Untuk onboarding siswa (10.7–10.9):**
  ```text
  Accept: application/json
  Authorization: Bearer {{siswa_token}}
  ```

Tidak ada header `Origin` / `X-XSRF-TOKEN` (alur token, bukan session).

### 0.3 Catatan perilaku penting

- **Identitas login:** siswa = NIS, guru = NIP, admin = username custom.
- **Route order:** `/api/siswa/me`, `/api/siswa/me/profil`, `/api/siswa/me/konfirmasi` wajib **sebelum** `/api/siswa/{id}`.
- **User Management (B4):** soft delete user. **Tidak boleh menghapus akun sendiri** → `422 { "message": "...", "code": "CANNOT_DELETE_SELF" }`. `PUT /api/users/{id}/password` adalah **jalur lupa password oleh admin** — cabut semua token & sesi web user.
- **Guru (B2):** buat user role_id=2 dengan `username = nip`. **Hapus guru → hard delete guru dan user terkait** (token dicabut).
- **Siswa (B2):** buat user role_id=3 dengan `username = nis`. **Hapus siswa → soft delete siswa, user tetap ada, semua token user dicabut**. Jika `nis` diupdate via admin, `users.username` **disinkronkan**. Update admin **tidak mereset `profil_confirmed_at`**.
- **Onboarding (B3):** `nis`/`username` **read-only** untuk siswa. Ditolak `422 PROFIL_SUDAH_DIKONFIRMASI` jika sudah konfirmasi. Belum lengkap → `422 PROFIL_BELUM_LENGKAP` + `errors`. Konfirmasi idempotent. Middleware `profil.confirmed` tersedia (belum dipasang ke route).
- **Template (B5):** admin-only, response file `.xlsx`.

### 0.4 Template Tests

- **T-Create (POST create):**
  ```javascript
  pm.test("Status 201 Created", function () {
      pm.response.to.have.status(201);
  });
  
  const json = pm.response.json();
  
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("id");
  });
  
  if (json.data && json.data.id) {
      // GANTI sesuai resource: user_id / guru_id / siswa_id
      pm.environment.set("user_id", json.data.id);
  }
  ```

- **T-OK (GET/PUT/DELETE sukses):**
  ```javascript
  pm.test("Status 2xx", function () {
      pm.expect(pm.response.code).to.be.oneOf([200, 204]);
  });
  
  const json = pm.response.json();
  if (json && json.message) {
      pm.test("Ada pesan", function () {
          pm.expect(json.message).to.be.a("string");
      });
  }
  ```

- **T-Detail (GET detail):**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Data sesuai ID", function () {
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("id");
  });
  ```

- **T-Error (validasi/otorisasi):**
  ```javascript
  pm.test("Status error sesuai skenario", function () {
      pm.expect(pm.response.code).to.be.oneOf([401, 403, 404, 422]);
  });
  ```

---

## 1. Folder `02. User Management` (B4)

Buat request **sesuai urutan di bawah**.

### 1.1 `GET List Users`

- **Method/URL:** `GET {{base_url}}/users`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Query Params (opsional):**
  - `role_id` (number)
  - `search` (string) — filter `name` / `username` / `email`
  - `page` (number)
  - `per_page` (number)
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response memiliki data array + meta", function () {
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.be.an("array");
      pm.expect(json).to.have.property("meta");
  });
  
  if (json.data && json.data.length > 0 && json.data[0].id) {
      pm.environment.set("user_id", json.data[0].id);
  }
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  List user (admin only). Bisa difilter berdasarkan role dan pencarian.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": [
      { "id": 1, "name": "Administrator", "username": "admin", "email": null, "role_id": 1, "role": { "id": 1, "nama": "admin" } }
    ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
  }
  ```

### 1.2 `GET Get User Detail`

- **Method/URL:** `GET {{base_url}}/users/{{user_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Memuat detail user + relasi", function () {
      pm.expect(json.data).to.have.property("id");
      pm.expect(json.data).to.have.property("role_id");
      pm.expect(json.data).to.have.property("role");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail user beserta relasi `role`, `guru`, `siswa`.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": {
      "id": 5,
      "name": "Guru Baru",
      "username": "199001012020011099",
      "email": null,
      "role_id": 2,
      "role": { "id": 2, "nama": "guru" },
      "guru": { "id": 1, "nip": "199001012020011099" }
    }
  }
  ```

### 1.3 `PUT Update User`

- **Method/URL:** `PUT {{base_url}}/users/{{user_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Nama User Update",
    "username": "username_baru",
    "email": "user@example.com",
    "role_id": 2
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update user. Admin only. `username` dan `email` harus unik.
  ```
- **Example (Success 200):**
  ```json
  {
    "message": "Pengguna berhasil diperbarui.",
    "data": { "id": 5, "name": "Nama User Update", "username": "username_baru", "email": "user@example.com", "role_id": 2 }
  }
  ```

### 1.4 `PUT Reset Password User`

- **Method/URL:** `PUT {{base_url}}/users/{{user_id}}/password`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Pesan reset berhasil", function () {
      pm.expect(json.message).to.be.a("string");
      pm.expect(json.message.toLowerCase()).to.include("berhasil");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  **Jalur lupa password oleh admin.** Reset kata sandi user. Setelah berhasil, semua token mobile user dan sesi web lain akan dicabut.
  
  ## Validasi
  - `password` minimal 8 karakter + konfirmasi wajib
  ```
- **Example (Success 200):**
  ```json
  { "message": "Kata sandi pengguna berhasil direset." }
  ```

### 1.5 `DELETE Delete User`

- **Method/URL:** `DELETE {{base_url}}/users/{{user_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Pesan soft delete", function () {
      pm.expect(json.message).to.be.a("string");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Soft delete user (admin only). 
  
  ## Aturan
  - Tidak boleh menghapus akun sendiri → `422` dengan `code: "CANNOT_DELETE_SELF"`
  - Cabut token & sesi sebelum dihapus
  ```
- **Example (Success 200):**
  ```json
  { "message": "Pengguna berhasil dihapus." }
  ```
- **Example (Error 422 - CANNOT_DELETE_SELF):**
  ```json
  { "message": "Tidak dapat menghapus akun sendiri.", "code": "CANNOT_DELETE_SELF" }
  ```

---

## 2. Folder `09. Data - Guru` (B2)

Buat request **sesuai urutan di bawah**.

### 2.1 `GET List Guru`

- **Method/URL:** `GET {{base_url}}/guru`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Query Params (opsional):**
  - `search` (string) — filter `nip` / `nama` / `username`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response memiliki data array + meta", function () {
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.be.an("array");
      pm.expect(json).to.have.property("meta");
  });
  
  if (json.data && json.data.length > 0 && json.data[0].id) {
      pm.environment.set("guru_id", json.data[0].id);
  }
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  List guru. Dapat diakses admin/guru.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": [
      { "id": 1, "nip": "199001012020011099", "nama": "Guru Contoh", "user": { "id": 5, "username": "199001012020011099" } }
    ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
  }
  ```

### 2.2 `GET Get Guru Detail`

- **Method/URL:** `GET {{base_url}}/guru/{{guru_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Detail guru + user", function () {
      pm.expect(json.data).to.have.property("id");
      pm.expect(json.data).to.have.property("nip");
      pm.expect(json.data).to.have.property("user");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail guru beserta relasi user.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": {
      "id": 1,
      "nip": "199001012020011099",
      "nama": "Guru Contoh",
      "user": { "id": 5, "name": "Guru Contoh", "username": "199001012020011099", "role_id": 2 }
    }
  }
  ```

### 2.3 `POST Create Guru`

- **Method/URL:** `POST {{base_url}}/guru`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Guru Test",
    "nip": "199001012020011099",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 201 Created", function () {
      pm.response.to.have.status(201);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("id");
  });
  
  if (json.data && json.data.id) {
      pm.environment.set("guru_id", json.data.id);
  }
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat guru baru. Admin only. Sistem akan otomatis membuat `users` dengan `role_id = 2` dan `username = nip`.
  
  ## Request Body
  | Field | Type | Required | Keterangan |
  |---|---|---|---|
  | name | string | Ya | Nama guru |
  | nip | string | Ya | Unik, akan jadi username login |
  | password | string | Ya | Minimal 8 karakter |
  | password_confirmation | string | Ya | Wajib sama dengan password |
  ```
- **Examples:**
  - `Success (201):`
    ```json
    {
      "message": "Guru berhasil ditambahkan.",
      "data": {
        "id": 1,
        "nip": "199001012020011099",
        "nama": "Guru Test",
        "user": { "id": 5, "name": "Guru Test", "username": "199001012020011099", "role_id": 2 }
      }
    }
    ```
  - `Validation Error (422) - Duplikat NIP:`
    ```json
    { "message": "The nip has already been taken.", "errors": { "nip": ["The nip has already been taken."] } }
    ```

### 2.4 `PUT Update Guru`

- **Method/URL:** `PUT {{base_url}}/guru/{{guru_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Guru Update",
    "nip": "199001012020011088"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update guru. Admin only. Jika `nip` diubah, `users.username` akan disinkronkan. `password` bersifat opsional.
  ```
- **Example (Success 200):**
  ```json
  {
    "message": "Guru berhasil diperbarui.",
    "data": { "id": 1, "nip": "199001012020011088", "nama": "Guru Update", "user": { "username": "199001012020011088" } }
  }
  ```

### 2.5 `DELETE Delete Guru`

- **Method/URL:** `DELETE {{base_url}}/guru/{{guru_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Pesan berhasil", function () {
      pm.expect(json.message).to.be.a("string");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus guru. Admin only. **Hard delete** guru dan **user terkait** ikut dihapus. Token user dicabut.
  ```
- **Example (Success 200):**
  ```json
  { "message": "Guru berhasil dihapus." }
  ```

### 2.6 `POST Bulk Upload Guru`

- **Method/URL:** `POST {{base_url}}/guru/bulk-upload`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Body (form-data):**
  | Key | Type | Value | Keterangan |
  |---|---|---|---|
  | file | File | `template-guru.xlsx` / file hasil edit | `.xlsx`, `.xls`, atau `.csv`. Maks 10MB |
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200/201", function () {
      pm.expect(pm.response.code).to.be.oneOf([200, 201]);
  });
  
  const json = pm.response.json();
  pm.test("Ada ringkasan hasil upload", function () {
      pm.expect(json).to.have.property("message");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Bulk upload guru via Excel. Admin only.
  
  ## Header Template
  `nip`, `nama` (wajib)
  
  ## Catatan
  - Password default: `password123`
  - `nip` otomatis jadi `username`
  - Baris gagal akan dilaporkan beserta alasan
  ```
- **Example (Success 200):**
  ```json
  { "message": "Berhasil import 5 guru.", "imported": 5, "failed": 0 }
  ```

### 2.7 `GET Siswa Bimbingan (Sprint 3)`

- **Method/URL:** `GET {{base_url}}/guru/{{guru_id}}/siswa-bimbingan`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Endpoint tersedia (placeholder)", function () {
      pm.expect(pm.response.code).to.be.oneOf([200, 404, 501]);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Placeholder untuk Sprint 3 (butuh tabel `lokasi_pkl`).
  ```

---

## 3. Folder `10. Data - Siswa` (B2 + B3)

Buat request **sesuai urutan di bawah**. Penting untuk menjaga urutan `10.7–10.9` tetap berurutan logis (onboarding).

### 3.1 `GET List Siswa`

- **Method/URL:** `GET {{base_url}}/siswa`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Query Params (opsional):**
  - `search` (string) — `nis` / `nama` / `username`
  - `jurusan_id` (number)
  - `gelombang_id` (number)
  - `kelas` (string)
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response memiliki data array + meta", function () {
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.be.an("array");
      pm.expect(json).to.have.property("meta");
  });
  
  if (json.data && json.data.length > 0 && json.data[0].id) {
      pm.environment.set("siswa_id", json.data[0].id);
  }
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  List siswa. Dapat diakses admin/guru.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": [
      { "id": 1, "nis": "0012345", "nama": "Siswa Contoh", "user": { "id": 10, "username": "0012345" }, "jurusan": null, "gelombang": null }
    ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
  }
  ```

### 3.2 `GET Get Siswa Detail`

- **Method/URL:** `GET {{base_url}}/siswa/{{siswa_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Detail siswa + relasi", function () {
      pm.expect(json.data).to.have.property("id");
      pm.expect(json.data).to.have.property("nis");
      pm.expect(json.data).to.have.property("user");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail siswa + user + jurusan + gelombang.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": {
      "id": 1,
      "nis": "0012345",
      "nama": "Siswa Contoh",
      "user": { "id": 10, "name": "Siswa Contoh", "username": "0012345", "role_id": 3, "email": null },
      "jurusan": { "id": 1, "nama": "Teknik Jaringan Komputer dan Telekomunikasi", "kode": "TJKT" },
      "gelombang": { "id": 1, "tahun_ajaran": "2026/2027", "nama": "Gelombang 1" }
    }
  }
  ```

### 3.3 `POST Create Siswa`

- **Method/URL:** `POST {{base_url}}/siswa`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Siswa Test",
    "nis": "0012999",
    "password": "password123",
    "password_confirmation": "password123",
    "jurusan_id": 1,
    "gelombang_id": 1,
    "jenis_kelamin": "L",
    "kelas": "XII TJKT 1",
    "telp": "081234567890",
    "alamat": "Jl. Pemuda No. 1, Magelang",
    "nama_ortu": "Bapak Siswa",
    "telp_ortu": "081298765432",
    "alamat_ortu": "Jl. Pemuda No. 1, Magelang"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 201 Created", function () {
      pm.response.to.have.status(201);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("id");
  });
  
  if (json.data && json.data.id) {
      pm.environment.set("siswa_id", json.data.id);
  }
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat siswa baru. Admin only. Sistem akan otomatis membuat `users` dengan `role_id = 3` dan `username = nis`. Field profil boleh dikosongkan (diisi saat onboarding).
  
  ## Request Body
  | Field | Type | Required | Keterangan |
  |---|---|---|---|
  | name | string | Ya | Nama siswa |
  | nis | string | Ya | Unik, akan jadi username login |
  | password | string | Ya | Minimal 8 karakter |
  | password_confirmation | string | Ya | Wajib sama |
  | jurusan_id/gelombang_id/kelas/jenis_kelamin/telp/alamat/nama_ortu/telp_ortu/alamat_ortu | bervariasi | Tidak | Opsional |
  ```
- **Examples:**
  - `Success (201):`
    ```json
    {
      "message": "Siswa berhasil ditambahkan.",
      "data": {
        "id": 2,
        "nis": "0012999",
        "nama": "Siswa Test",
        "user": { "id": 11, "name": "Siswa Test", "username": "0012999", "role_id": 3 },
        "profil_confirmed_at": null
      }
    }
    ```
  - `Validation Error (422):`
    ```json
    { "message": "The nis has already been taken.", "errors": { "nis": ["The nis has already been taken."] } }
    ```

### 3.4 `PUT Update Siswa`

- **Method/URL:** `PUT {{base_url}}/siswa/{{siswa_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Siswa Update",
    "nis": "0013001",
    "kelas": "XII TJKT 2"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update data user & profil siswa. Admin only. Jika `nis` diubah, `users.username` **disinkronkan**. **Tidak mereset `profil_confirmed_at`**. `password` bersifat opsional.
  ```
- **Example (Success 200):**
  ```json
  {
    "message": "Siswa berhasil diperbarui.",
    "data": {
      "id": 2,
      "nis": "0013001",
      "nama": "Siswa Update",
      "user": { "username": "0013001" },
      "profil_confirmed_at": null
    }
  }
  ```

### 3.5 `DELETE Delete Siswa`

- **Method/URL:** `DELETE {{base_url}}/siswa/{{siswa_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Pesan berhasil", function () {
      pm.expect(json.message).to.be.a("string");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus siswa. Admin only. **Soft delete** siswa, **user tetap ada**. Semua token user dicabut. Data histori tetap utuh.
  ```
- **Example (Success 200):**
  ```json
  { "message": "Siswa berhasil dihapus." }
  ```

### 3.6 `POST Bulk Upload Siswa`

- **Method/URL:** `POST {{base_url}}/siswa/bulk-upload`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Body (form-data):**
  | Key | Type | Value | Keterangan |
  |---|---|---|---|
  | file | File | `template-siswa.xlsx` / file hasil edit | `.xlsx`, `.xls`, atau `.csv`. Maks 10MB |
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200/201", function () {
      pm.expect(pm.response.code).to.be.oneOf([200, 201]);
  });
  
  const json = pm.response.json();
  pm.test("Ada ringkasan hasil upload", function () {
      pm.expect(json).to.have.property("message");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Bulk upload siswa via Excel. Admin only.
  
  ## Header Template
  `nis`, `nama` (wajib). Opsional: `jenis_kelamin`, `kelas`, `jurusan`, `gelombang`, `telp`, `email`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`
  
  ## Catatan
  - Password default: `password123`
  - `nis` dinormalisasi (hindari notasi ilmiah)
  - `jenis_kelamin`: `L`/`P` atau `Laki-laki`/`Perempuan` (dipetakan otomatis)
  - `jurusan`: bisa kode atau nama (case-insensitive)
  - `gelombang`: berdasarkan nama gelombang
  - Kolom profil boleh kosong → dilengkapi saat onboarding
  ```
- **Example (Success 200):**
  ```json
  { "message": "Berhasil import 20 siswa.", "imported": 20, "failed": 0 }
  ```

### 3.7 `GET Saya (Me)`

- **Method/URL:** `GET {{base_url}}/siswa/me`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Memuat profil siswa login", function () {
      pm.expect(json.data).to.have.property("nis");
      pm.expect(json.data).to.have.property("nama");
      pm.expect(json.data).to.have.property("user");
      pm.expect(json.data).to.have.property("akademik");
      pm.expect(json.data).to.have.property("profil_terkonfirmasi");
      pm.expect(json.data).to.have.property("profil_lengkap");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Ambil profil siswa yang sedang login (role siswa). Digunakan untuk alur onboarding.
  ```
- **Example (Success 200):**
  ```json
  {
    "data": {
      "id": 2,
      "nis": "0012999",
      "nama": "Siswa Test",
      "user": { "id": 11, "name": "Siswa Test", "username": "0012999", "email": null },
      "akademik": { "jurusan": null, "gelombang": null, "kelas": null },
      "profil_terkonfirmasi": false,
      "profil_lengkap": false
    }
  }
  ```

### 3.8 `PUT Update Profil Saya`

- **Method/URL:** `PUT {{base_url}}/siswa/me/profil`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Nama Siswa",
    "email": "siswa@example.com",
    "jenis_kelamin": "L",
    "telp": "081234567890",
    "alamat": "Jl. Pemuda No. 1, Magelang",
    "kelas": "XII TJKT 1",
    "jurusan_id": 1,
    "gelombang_id": 1,
    "nama_ortu": "Bapak Siswa",
    "telp_ortu": "081298765432",
    "alamat_ortu": "Jl. Pemuda No. 1, Magelang"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update profil siswa sendiri (parsial). Hanya bisa dilakukan **sebelum** konfirmasi.
  
  ## Aturan
  - `nis` dan `username` **diabaikan** jika dikirim
  - Jika sudah dikonfirmasi → `422` dengan `code: "PROFIL_SUDAH_DIKONFIRMASI"`
  - Data ke `users` (contoh `name`, `email`) bisa diupdate lewat endpoint ini
  ```
- **Examples:**
  - `Success (200):`
    ```json
    { "message": "Profil berhasil diperbarui.", "data": { "nama": "Nama Siswa", "email": "siswa@example.com", "profil_terkonfirmasi": false } }
    ```
  - `Error (422) - Sudah Konfirmasi:`
    ```json
    { "message": "Profil sudah dikonfirmasi dan tidak dapat diubah.", "code": "PROFIL_SUDAH_DIKONFIRMASI" }
    ```

### 3.9 `PUT Konfirmasi Profil`

- **Method/URL:** `PUT {{base_url}}/siswa/me/konfirmasi`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Body:** (kosong / tidak diperlukan)
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Response berisi message & data", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Konfirmasi profil siswa. Akan divalidasi kelengkapan data.
  
  ## Aturan
  - Jika belum lengkap → `422` `PROFIL_BELUM_LENGKAP` dengan `errors` per field wajib
  - Jika sudah lengkap → set `profil_confirmed_at`
  - **Idempotent** — jika sudah dikonfirmasi, akan tetap mengembalikan `200`
  ```
- **Examples:**
  - `Success (200) - Pertama kali:`
    ```json
    { "message": "Profil berhasil dikonfirmasi.", "data": { "profil_terkonfirmasi": true, "profil_confirmed_at": "2026-10-25T10:00:00.000000Z" } }
    ```
  - `Error (422) - Belum Lengkap:`
    ```json
    {
      "message": "Data profil belum lengkap.",
      "code": "PROFIL_BELUM_LENGKAP",
      "errors": {
        "nama_ortu": ["Nama orang tua/wali wajib diisi."],
        "telp_ortu": ["Nomor telepon orang tua/wali wajib diisi."]
      }
    }
    ```
  - `Success (200) - Konfirmasi ulang (idempotent):`
    ```json
    { "message": "Profil sudah dikonfirmasi.", "data": { "profil_terkonfirmasi": true } }
    ```

### 3.10 `GET Lokasi PKL Siswa (Sprint 3)`

- **Method/URL:** `GET {{base_url}}/siswa/{{siswa_id}}/lokasi-pkl`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Endpoint tersedia (placeholder)", function () {
      pm.expect(pm.response.code).to.be.oneOf([200, 404, 501]);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Placeholder untuk Sprint 3 (butuh tabel `lokasi_pkl`).
  ```

---

## 4. Folder `19. Template Excel` (B5)

Buat request **sesuai urutan di bawah**. Semua request **admin only** dan mengembalikan file `.xlsx`.

### 4.1 `GET Download Template Guru`

- **Method/URL:** `GET {{base_url}}/templates/guru`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  pm.test("Content-Type adalah xlsx", function () {
      const ct = pm.response.headers.get("Content-Type");
      pm.expect(ct).to.include("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
  });
  
  pm.test("Ada Content-Disposition", function () {
      pm.expect(pm.response.headers.has("Content-Disposition")).to.be.true;
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Download template Excel untuk bulk upload guru.
  
  ## Header
  `nip`, `nama`
  ```
- **Example:** `Success (200)` — file download `template-guru.xlsx`

### 4.2 `GET Download Template Siswa`

- **Method/URL:** `GET {{base_url}}/templates/siswa`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  pm.test("Content-Type adalah xlsx", function () {
      const ct = pm.response.headers.get("Content-Type");
      pm.expect(ct).to.include("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Download template Excel untuk bulk upload siswa.
  
  ## Header
  `nis`, `nama`, `jenis_kelamin`, `kelas`, `jurusan`, `gelombang`, `telp`, `email`, `alamat`, `nama_ortu`, `telp_ortu`, `alamat_ortu`
  ```
- **Example:** `Success (200)` — file download `template-siswa.xlsx`

### 4.3 `GET Download Template Perusahaan`

- **Method/URL:** `GET {{base_url}}/templates/perusahaan`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  pm.test("Content-Type adalah xlsx", function () {
      const ct = pm.response.headers.get("Content-Type");
      pm.expect(ct).to.include("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Download template Excel untuk bulk upload perusahaan (admin only).
  ```
- **Example:** `Success (200)` — file download `template-perusahaan.xlsx`

### 4.4 `GET Download Template Kompetensi`

- **Method/URL:** `GET {{base_url}}/templates/kompetensi`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  pm.test("Content-Type adalah xlsx", function () {
      const ct = pm.response.headers.get("Content-Type");
      pm.expect(ct).to.include("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Download template Excel untuk bulk upload kompetensi (admin only).
  ```
- **Example:** `Success (200)` — file download `template-kompetensi.xlsx`

---

## 5. Tambahan Folder `99. Test Scenarios`

Tambahkan request berikut dengan prefix `[Sprint2]`. Semua adalah skenario error/edge case.

### 5.1 `[Sprint2][User Mgmt] GET List Users - Sebagai Guru (Forbidden)`

- **Method/URL:** `GET {{base_url}}/users`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{guru_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 403 Forbidden", function () {
      pm.response.to.have.status(403);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  User Management hanya bisa diakses admin.
  ```

### 5.2 `[Sprint2][User Mgmt] DELETE User - Hapus Akun Sendiri`

- **Method/URL:** `DELETE {{base_url}}/users/{{admin_user_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });
  
  const json = pm.response.json();
  pm.test("Memiliki code CANNOT_DELETE_SELF", function () {
      pm.expect(json.code || json.errors || json.message).to.exist;
      if (json.code) pm.expect(json.code).to.eql("CANNOT_DELETE_SELF");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Tidak boleh menghapus akun sendiri.
  
  ## Catatan
  Pastikan `{{admin_user_id}}` diisi dengan ID user yang sedang login sebagai admin.
  ```

### 5.3 `[Sprint2][User Mgmt] PUT Reset Password - Cabut Token`

- **Method/URL:** `PUT {{base_url}}/users/{{user_id}}/password`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Pesan berhasil", function () {
      pm.expect(json.message).to.be.a("string");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Reset password oleh admin (jalur lupa password). Semua token & sesi web user akan dicabut.
  ```

### 5.4 `[Sprint2][Guru] POST Create Guru - NIP Duplikat`

- **Method/URL:** `POST {{base_url}}/guru`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Guru Duplikat",
    "nip": "199001012020011099",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Validasi unique NIP/username guru.
  ```

### 5.5 `[Sprint2][Siswa] POST Create Siswa - NIS Duplikat`

- **Method/URL:** `POST {{base_url}}/siswa`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Siswa Duplikat",
    "nis": "0012345",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Validasi unique NIS/username siswa.
  ```

### 5.6 `[Sprint2][Siswa] PUT Update Siswa - Sync Username`

- **Method/URL:** `PUT {{base_url}}/siswa/{{siswa_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "nis": "0013001"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Username ikut tersinkron", function () {
      pm.expect(json.data.user.username).to.eql("0013001");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Jika NIS diupdate admin, `users.username` ikut berubah.
  ```

### 5.7 `[Sprint2][Siswa] DELETE Siswa - Soft Delete`

- **Method/URL:** `DELETE {{base_url}}/siswa/{{siswa_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Siswa di-soft delete, user tetap ada, semua token user dicabut.
  ```

### 5.8 `[Sprint2][Onboarding] PUT Konfirmasi - Belum Lengkap`

- **Method/URL:** `PUT {{base_url}}/siswa/me/konfirmasi`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });
  
  const json = pm.response.json();
  pm.test("Memiliki code PROFIL_BELUM_LENGKAP", function () {
      pm.expect(json.code).to.eql("PROFIL_BELUM_LENGKAP");
      pm.expect(json).to.have.property("errors");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Validasi kelengkapan profil saat konfirmasi. Mengembalikan `errors` per field wajib.
  ```

### 5.9 `[Sprint2][Onboarding] PUT Update Profil - Sudah Konfirmasi`

- **Method/URL:** `PUT {{base_url}}/siswa/me/profil`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Coba Ubah"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 422", function () {
      pm.response.to.have.status(422);
  });
  
  const json = pm.response.json();
  pm.test("Memiliki code PROFIL_SUDAH_DIKONFIRMASI", function () {
      pm.expect(json.code).to.eql("PROFIL_SUDAH_DIKONFIRMASI");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Siswa tidak bisa mengubah profil sendiri setelah dikonfirmasi. Hanya admin yang bisa mengubah.
  ```

### 5.10 `[Sprint2][Onboarding] PUT Konfirmasi - Idempotent`

- **Method/URL:** `PUT {{base_url}}/siswa/me/konfirmasi`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("Idempotent", function () {
      pm.expect(json.data.profil_terkonfirmasi).to.be.true;
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Konfirmasi ulang tetap mengembalikan 200 (idempotent).
  ```

### 5.11 `[Sprint2][Onboarding] PUT Update Profil - NIS Diabaikan`

- **Method/URL:** `PUT {{base_url}}/siswa/me/profil`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "name": "Nama Update",
    "nis": "9999999"
  }
  ```
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });
  
  const json = pm.response.json();
  pm.test("NIS/username tidak berubah", function () {
      pm.expect(json.data.nis).to.not.eql("9999999");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Field `nis`/`username` diabaikan saat siswa mengupdate profil sendiri.
  ```

### 5.12 `[Sprint2][Template] GET Template - Sebagai Siswa (Forbidden)`

- **Method/URL:** `GET {{base_url}}/templates/siswa`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 403 Forbidden", function () {
      pm.response.to.have.status(403);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Template Excel hanya bisa diakses admin.
  ```

### 5.13 `[Sprint2][Template] GET Template - Tipe Tidak Valid`

- **Method/URL:** `GET {{base_url}}/templates/unknown`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{admin_token}}`
- **Pre-request Script:** (kosong)
- **Tests:**
  ```javascript
  pm.test("Status 404", function () {
      pm.response.to.have.status(404);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Validasi tipe template yang tidak didukung.
  ```

---

## 6. Urutan Eksekusi Rekomendasi

1. **01. Authentication** → Login admin, guru, siswa (isi `admin_token`, `guru_token`, `siswa_token`)
2. **02. User Management** (1.1 – 1.5)
3. **09. Data - Guru** (2.1 – 2.6)
4. **10. Data - Siswa** (3.1 – 3.6) lalu (3.7 – 3.9)
5. **19. Template Excel** (4.1 – 4.4)
6. **99. Test Scenarios** (5.1 – 5.13)

> **Catatan:** Untuk `3.7–3.9` (onboarding), pastikan menggunakan `{{siswa_token}}` dan route `/siswa/me*` terdaftar sebelum `/siswa/{id}`.

---

## 7. Troubleshooting

| Gejala | Penyebab / Solusi |
|---|---|
| 401 di request admin | `{{admin_token}}` kosong → login via `01. Authentication` dengan header `X-Client: mobile` |
| 403 di write guru/siswa/user management | Token bukan admin → pastikan pakai `{{admin_token}}` |
| `GET /siswa/me` 404 | Urutan route salah (route `{id}` menangkap `me`) → pastikan `/siswa/me*` didefinisikan sebelum `/siswa/{id}` |
| `PUT /siswa/me/profil` 422 `PROFIL_SUDAH_DIKONFIRMASI` | Siswa sudah dikonfirmasi → reset data atau pakai siswa lain yang belum dikonfirmasi |
| `PUT /siswa/me/konfirmasi` 422 `PROFIL_BELUM_LENGKAP` | Masih ada field wajib kosong → lengkapi via `PUT /siswa/me/profil` dulu |
| Delete guru menghapus user | Perilaku yang diharapkan (hard delete). Gunakan siswa untuk menguji soft delete |
| Delete siswa tetap ada user-nya | Perilaku yang diharapkan (soft delete). Cek via `GET /users` sebagai admin |
| Reset password admin tidak mempengaruhi token lama | Pastikan menggunakan `PUT /api/users/{id}/password` (bukan `PUT /api/user/password`) |

---

## 8. Referensi

- API Spec: `.agents/tech-specs/2026/003-api-endpoints.md`
- DB Schema: `.agents/tech-specs/2026/002-database-schema.md`
- Sprint Plan: `.agents/tasks/sprint-2-user-detail-plan.md`
- Backend Roadmap: `.agents/tasks/backend-roadmap.md`
- Postman Guide: `.agents/tech-specs/2026/004-postman-collection-guide.md`
- Auth Playbook: `.agents/tasks/postman-auth-execution.md`
- Master Data Playbook: `.agents/tasks/postman-master-data-execution.md`
