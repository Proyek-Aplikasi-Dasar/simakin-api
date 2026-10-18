# Playbook Eksekusi Postman — Autentikasi (Sprint 0)

Dokumen kerja untuk **membangun koleksi Postman secara manual** (di GUI Postman) bagi endpoint autentikasi yang sudah selesai. Isi di sini sudah siap-tempel: headers, body, *Pre-request Script*, *Tests*, isi tab *Documentation*, dan *Examples*.

Panduan konsep/umum: `.agents/tech-specs/2026/004-postman-collection-guide.md`.

**Ruang lingkup:** 5 endpoint autentikasi yang sudah *Done*
`POST /api/login`, `POST /api/logout`, `GET /api/user`, `POST /api/register`, `PUT /api/user/password`.

---

## 0. Prasyarat

1. Backend jalan:
   ```sh
   php artisan serve            # http://localhost:8000
   php artisan migrate --seed   # pastikan user demo ada
   ```
2. `.env` lokal memuat (untuk login web stateful):
   ```
   APP_URL=http://localhost:8000
   FRONTEND_URL=http://localhost:5173
   SANCTUM_STATEFUL_DOMAINS=localhost:5173
   SESSION_DRIVER=database
   ```
3. Postman Desktop terpasang.
4. User demo (hasil seeder): `admin@example.com/admin123`, `guru@example.com/guru123`, `siswa@example.com/siswa123`.

> Nilai `frontend_url` **harus sama** dengan `SANCTUM_STATEFUL_DOMAINS`. Jika berbeda, login web gagal (cookie session tidak tersimpan).

---

## 1. Buat Environment `SIMAKIN Local`

Postman → **Environments** → **Create Environment** → nama `SIMAKIN Local`. Isi variables:

| Variable | Initial Value | Keterangan |
|---|---|---|
| `base_url` | `http://localhost:8000/api` | API base |
| `web_url` | `http://localhost:8000` | Host API (untuk CSRF cookie) |
| `frontend_url` | `localhost:5173` | Asal SPA → header `Origin` |
| `token` | _(kosong)_ | Diisi otomatis saat login mobile |
| `user_id` | _(kosong)_ | Diisi otomatis |
| `role_id` | _(kosong)_ | Diisi otomatis |
| `xsrf_token` | _(kosong)_ | Diisi otomatis (cookie XSRF-TOKEN) |
| `admin_email` | `admin@example.com` | Akun admin |
| `admin_password` | `admin123` | |
| `guru_email` | `guru@example.com` | Akun guru |
| `guru_password` | `guru123` | |
| `siswa_email` | `siswa@example.com` | Akun siswa |
| `siswa_password` | `siswa123` | |

Simpan (Ctrl+S) lalu pilih environment ini di kanan atas Postman.

---

## 2. Buat Collection & Folder

Buat collection **`SIMAKIN API`**, lalu buat dua folder:
- `01. Authentication`
- `99. Test Scenarios`

---

## 3. Folder `01. Authentication`

Buat request **sesuai urutan di bawah** (urutan ini penting untuk Collection Runner).

### 3.1 `GET Fetch CSRF Cookie`

- **Method/URL:** `GET {{web_url}}/sanctum/csrf-cookie`
- **Headers:**
  ```
  Accept: application/json
  ```
- **Tests:**
  ```javascript
  pm.test("Status 204 No Content", function () {
      pm.response.to.have.status(204);
  });

  // Simpan cookie XSRF-TOKEN (URL-decoded) untuk request web berikutnya
  const xsrf = pm.cookies.get("XSRF-TOKEN");
  if (xsrf) {
      pm.environment.set("xsrf_token", decodeURIComponent(xsrf));
  }
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Mengambil cookie CSRF (XSRF-TOKEN) dari Sanctum. Wajib dijalankan sebelum request web (login/register/ganti sandi via session).

  ## Response
  - 204 No Content, cookie `XSRF-TOKEN` & `laravel-session` tersimpan di cookie jar.
  ```
- **Example:** `204 No Content` (body kosong).

---

### 3.2 `POST Login (Web)`

- **Method/URL:** `POST {{base_url}}/login`
- **Pre-request Script:**
  ```javascript
  // Ambil cookie CSRF bila belum ada (fallback bila 3.1 belum dijalankan)
  if (!pm.cookies.get("XSRF-TOKEN")) {
      pm.sendRequest({
          url: pm.environment.get("web_url") + "/sanctum/csrf-cookie",
          method: "GET",
          header: { "Accept": "application/json" }
      }, function () {});
  }

  const xsrf = pm.cookies.get("XSRF-TOKEN");
  if (xsrf) {
      pm.environment.set("xsrf_token", decodeURIComponent(xsrf));
  }
  ```
- **Headers:**
  ```
  Accept: application/json
  Origin: {{frontend_url}}
  X-XSRF-TOKEN: {{xsrf_token}}
  ```
- **Body** (raw → JSON):
  ```json
  { "email": "{{admin_email}}", "password": "{{admin_password}}" }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Response berisi user", function () {
      pm.expect(json).to.have.property("user");
      pm.expect(json.user).to.have.property("id");
  });

  if (json.user) {
      pm.environment.set("user_id", json.user.id);
      pm.environment.set("role_id", json.user.role_id);
  }
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Login web (admin/guru) berbasis session. Tidak mengembalikan token; server menyetel cookie session.

  ## Authorization
  - Header `Origin: {{frontend_url}}` (harus cocok SANCTUM_STATEFUL_DOMAINS)
  - Header `X-XSRF-TOKEN` dari cookie XSRF-TOKEN

  ## Request Body
  | Field | Type | Required |
  |---|---|---|
  | email | string | Ya |
  | password | string | Ya |

  ## Error
  - 422 `Kredensial tidak valid.` (errors.email)

  ## Catatan
  Login web tidak mengembalikan token. Request web berikutnya memakai cookie session otomatis.
  ```
- **Examples:**
  - `Success (200)`:
    ```json
    {
      "user": {
        "id": 1, "name": "Administrator", "email": "admin@example.com",
        "email_verified_at": "2026-09-30T04:02:07.000000Z",
        "role_id": 1, "created_at": "2026-09-30T04:02:07.000000Z",
        "updated_at": "2026-09-30T04:02:07.000000Z"
      }
    }
    ```
  - `Validation Error (422)`:
    ```json
    { "message": "Kredensial tidak valid.", "errors": { "email": ["Kredensial tidak valid."] } }
    ```

---

### 3.2b `GET Fetch CSRF Cookie (Refresh)`

> **Wajib dijalankan setelah `3.2`.** `POST Login (Web)` memanggil `session()->regenerate()` sehingga token CSRF diganti; request web berikutnya (Register, 4.6, 4.7) akan **419** bila cookie `XSRF-TOKEN` belum diperbarui. Request ini menyegarkan cookie agar cocok dengan session aktif.

- **Method/URL:** `GET {{web_url}}/sanctum/csrf-cookie`
- **Headers:** sama seperti `3.1` (`Accept: application/json`)
- **Tests:** sama seperti `3.1` (status 204 + set `{{xsrf_token}}` dari cookie).
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Menyegarkan cookie CSRF (XSRF-TOKEN) setelah `POST Login (Web)` (session regenerate). Jalankan sebelum request web yang butuh session admin (Register, dll.).
  ```
- **Example:** `204 No Content`.

---

### 3.3 `POST Login (Mobile)`

- **Method/URL:** `POST {{base_url}}/login`
- **Headers:**
  ```
  Accept: application/json
  X-Client: mobile
  ```
- **Body** (raw → JSON):
  ```json
  { "email": "{{siswa_email}}", "password": "{{siswa_password}}" }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Mengembalikan token", function () {
      pm.expect(json).to.have.property("token");
      pm.expect(json.token).to.be.a("string").and.not.empty;
  });

  if (json.token) {
      pm.environment.set("token", json.token);
      pm.environment.set("user_id", json.user.id);
      pm.environment.set("role_id", json.user.role_id);
  }
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Login mobile (siswa) berbasis Bearer token. Header `X-Client: mobile` menandakan klien mobile.

  ## Request Body
  | Field | Type | Required |
  |---|---|---|
  | email | string | Ya |
  | password | string | Ya |

  ## Response
  - 200: `{ user, token }` — simpan `token` untuk request berikutnya.

  ## Error
  - 422 `Kredensial tidak valid.`
  ```
- **Examples:**
  - `Success (200)`:
    ```json
    {
      "user": { "id": 4, "name": "Siswa Teladan", "email": "siswa@example.com", "role_id": 3, "role": { "id": 3, "nama": "siswa" } },
      "token": "4|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
    ```
  - `Validation Error (422)`: sama seperti 3.2.

---

### 3.4 `GET Get User`

- **Method/URL:** `GET {{base_url}}/user`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  X-Client: mobile
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Data user sesuai", function () {
      pm.expect(json).to.have.property("id");
      pm.expect(json).to.have.property("email");
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Mengambil data user yang sedang login (mobile: kirim Bearer token).

  ## Authorization
  - `Authorization: Bearer {{token}}`, `X-Client: mobile`

  ## Error
  - 401 `Unauthenticated.` (token tidak ada/kedaluwarsa)
  ```
- **Examples:**
  - `Success (200)`: `{ "id": 4, "name": "Siswa Teladan", "email": "siswa@example.com", "role_id": 3, "role": { "id": 3, "nama": "siswa" } }`
  - `Unauthorized (401)`: `{ "message": "Unauthenticated." }`

---

### 3.5 `POST Register`

- **Method/URL:** `POST {{base_url}}/register`
- **Pre-request Script:** (sama seperti 3.2, agar cookie XSRF tersedia untuk session admin web)
  ```javascript
  if (!pm.cookies.get("XSRF-TOKEN")) {
      pm.sendRequest({
          url: pm.environment.get("web_url") + "/sanctum/csrf-cookie",
          method: "GET",
          header: { "Accept": "application/json" }
      }, function () {});
  }

  const xsrf = pm.cookies.get("XSRF-TOKEN");
  if (xsrf) {
      pm.environment.set("xsrf_token", decodeURIComponent(xsrf));
  }
  ```
- **Headers:**
  ```
  Accept: application/json
  Origin: {{frontend_url}}
  X-XSRF-TOKEN: {{xsrf_token}}
  ```
- **Body** (raw → JSON) — email dinamis agar tidak duplikat saat dijalankan ulang:
  ```json
  {
    "name": "Guru Uji {{$timestamp}}",
    "email": "guru.uji.{{$timestamp}}@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role_id": 2
  }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 201 Created", function () {
      pm.response.to.have.status(201);
  });

  const json = pm.response.json();

  pm.test("Response berisi data user baru", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("email");
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Admin mendaftarkan akun baru (guru/admin). Akun siswa dibuat lewat menu data siswa.

  ## Authorization
  - Role: admin (wajib login admin lebih dulu via 3.2)

  ## Request Body
  | Field | Type | Required | Catatan |
  |---|---|---|---|
  | name | string | Ya | |
  | email | string | Ya | Unik |
  | password | string | Ya | Min 8, harus sama dengan password_confirmation |
  | password_confirmation | string | Ya | |
  | role_id | integer | Ya | Hanya 1 (admin) atau 2 (guru) |

  ## Error
  - 401 belum login
  - 403 bukan admin
  - 422 `role_id` bukan admin/guru, email duplikat, atau password tidak valid
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Pengguna berhasil didaftarkan.",
      "data": { "id": 5, "name": "Guru Uji", "email": "guru.uji@example.com", "role_id": 2, "role": { "id": 2, "nama": "guru" } }
    }
    ```
  - `Forbidden (403)`: `{ "message": "Tidak diizinkan." }`
  - `Validation Error (422)`: `{ "message": "...", "errors": { "role_id": ["Role harus admin atau guru. Akun siswa dibuat melalui menu data siswa."] } }`

---

### 3.6 `PUT Change Password`

- **Method/URL:** `PUT {{base_url}}/user/password`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  X-Client: mobile
  ```
- **Body** (raw → JSON):
  ```json
  {
    "current_password": "{{siswa_password}}",
    "password": "password456",
    "password_confirmation": "password456"
  }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  pm.test("Pesan sukses", function () {
      const json = pm.response.json();
      pm.expect(json.message).to.eql("Kata sandi berhasil diubah.");
  });
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Ubah kata sandi sendiri (web & mobile). Setelah sukses, semua token mobile lain dan sesi web lain dicabut (kecuali sesi saat ini).

  ## Request Body
  | Field | Type | Required |
  |---|---|---|
  | current_password | string | Ya |
  | password | string | Ya (min 8, harus sama dengan password_confirmation) |
  | password_confirmation | string | Ya |

  ## Efek samping
  - Token mobile lain milik user dihapus.
  - Password berubah → jalankan `3.7` untuk mengembalikan ke `siswa123`.

  ## Error
  - 401 belum login
  - 422 `current_password` salah / konfirmasi tidak cocok / password < 8
  ```
- **Examples:**
  - `Success (200)`: `{ "message": "Kata sandi berhasil diubah." }`
  - `Validation Error (422)`: `{ "message": "Kata sandi saat ini tidak sesuai.", "errors": { "current_password": ["Kata sandi saat ini tidak sesuai."] } }`

---

### 3.7 `PUT Change Password (Kembalikan)`

Mengembalikan password siswa ke nilai semula agar data seeder tidak berubah.

- **Method/URL:** `PUT {{base_url}}/user/password`
- **Headers:** sama seperti 3.6
- **Body** (raw → JSON):
  ```json
  {
    "current_password": "password456",
    "password": "{{siswa_password}}",
    "password_confirmation": "{{siswa_password}}"
  }
  ```
- **Tests:** sama seperti 3.6.
- **Documentation:** "Mengembalikan kata sandi akun uji ke `{{siswa_password}}` setelah tes 3.6."

---

### 3.8 `POST Logout`

- **Method/URL:** `POST {{base_url}}/logout`
- **Headers:**
  ```
  Accept: application/json
  Authorization: Bearer {{token}}
  X-Client: mobile
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  pm.test("Pesan logout", function () {
      const json = pm.response.json();
      pm.expect(json).to.have.property("message");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Logout. Untuk mobile: menghapus Bearer token yang dipakai. Untuk web: menghancurkan session.
  ## Error
  - 401 belum login
  ```
- **Examples:** `Success (200)`: `{ "message": "Berhasil logout." }`

---

## 4. Folder `99. Test Scenarios`

Jalankan **setelah** folder `01`. Urutan berikut menjaga session admin web tetap hidup dan token siswa diperbarui.

> **Konvensi penamaan:** folder ini bersifat flat dan lintas modul, jadi setiap request diberi prefix `[Modul]` (mis. `[Auth]`, `[User]`, `[Jurusan]`) atau `[Flow]` untuk alur lintas modul. Nomor subpoin (4.1, 4.2, …) dipertahankan hanya sebagai urutan referensi Collection Runner.

> **Penting:** semua request di bawah ini adalah **skenario error** (kecuali 4.1 & 4.10). Test-nya **wajib** meng-assert **kode error** yang diharapkan (401/403/422), **bukan** `200`/`201`. Jangan menyalin test happy-path dari folder `01`.

### 4.1 `[Auth] POST Login (Mobile) - Refresh Token`
Sama seperti `3.3` (siswa). Fungsinya memperbarui `{{token}}` yang mungkin sudah tidak valid.
- **Tests:** 200 + simpan `token`.

### 4.2 `[Auth] POST Login - Password Salah`
- `POST {{base_url}}/login`, headers `X-Client: mobile`, body `{ "email": "{{siswa_email}}", "password": "salah123" }`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.email", function () {
      pm.expect(pm.response.json().errors).to.have.property("email");
  });
  ```

### 4.3 `[Auth] POST Login - Field Kosong`
- `POST {{base_url}}/login`, headers `X-Client: mobile`, body `{}`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("errors.email & errors.password", function () {
      const e = pm.response.json().errors;
      pm.expect(e).to.have.property("email");
      pm.expect(e).to.have.property("password");
  });
  ```

### 4.4 `[Auth] GET Get User - Tanpa Token`
- `GET {{base_url}}/user`, headers `Accept: application/json` (tanpa Authorization)
- **Tests:**
  ```javascript
  pm.test("Status 401", function () { pm.response.to.have.status(401); });
  ```

### 4.5 `[Auth] POST Register - Bukan Admin`
- `POST {{base_url}}/register`, headers `Accept`, `Authorization: Bearer {{token}}` (siswa), `X-Client: mobile`
- Body: seperti 3.5 (guru) — boleh statis.
- **Tests:**
  ```javascript
  pm.test("Status 403", function () { pm.response.to.have.status(403); });
  ```

### 4.6 `[Auth] POST Register - Role Siswa`
- Web admin session: headers `Accept`, `Origin: {{frontend_url}}`, `X-XSRF-TOKEN: {{xsrf_token}}`
- Pre-request script sama seperti 3.2.
- Body:
  ```json
  { "name": "Uji Siswa", "email": "uji.siswa.{{$timestamp}}@example.com", "password": "password123", "password_confirmation": "password123", "role_id": 3 }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.role_id", function () {
      pm.expect(pm.response.json().errors).to.have.property("role_id");
  });
  ```

### 4.7 `[Auth] POST Register - Email Duplikat`
- Web admin session (headers seperti 4.6).
- Body pakai email yang sudah ada:
  ```json
  { "name": "Duplikat", "email": "{{admin_email}}", "password": "password123", "password_confirmation": "password123", "role_id": 2 }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.email", function () {
      pm.expect(pm.response.json().errors).to.have.property("email");
  });
  ```

### 4.8 `[Auth] PUT Change Password - Current Salah`
- Headers seperti 3.6 (Bearer `{{token}}`).
- Body: `{ "current_password": "salahsekali", "password": "password456", "password_confirmation": "password456" }`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.current_password", function () {
      pm.expect(pm.response.json().errors).to.have.property("current_password");
  });
  ```

### 4.9 `[Auth] PUT Change Password - Konfirmasi Beda`
- Headers seperti 3.6.
- Body: `{ "current_password": "{{siswa_password}}", "password": "password456", "password_confirmation": "beda12345" }`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.password", function () {
      pm.expect(pm.response.json().errors).to.have.property("password");
  });
  ```

### 4.10 `[Auth] POST Logout - Cleanup`
- Sama seperti `3.8`. **Tests:** 200.

### 4.11 `[Auth] GET Get User - Setelah Logout`
- Sama seperti `3.4` (pakai `{{token}}` yang sudah di-logout).
- **Tests:**
  ```javascript
  pm.test("Status 401", function () { pm.response.to.have.status(401); });
  ```

---

## 5. Jalankan Collection Runner

1. Klik collection **SIMAKIN API** → tombol **Run**.
2. Environment: **SIMAKIN Local**.
3. Iteration `1`, Delay `100 ms`.
4. **Run**.
5. Semua test harus **PASS**. Jika ada merah, cek §7.

---

## 6. Export & Commit (dilakukan sendiri)

1. Export collection: collection → **⋯** → **Export** → format **Collection v2.1** → simpan `docs/postman/SIMAKIN-API-Collection.json`.
2. Export environment: Environments → `SIMAKIN Local` → **⋯** → **Export** → simpan `docs/postman/SIMAKIN-Local.postman_environment.json`.
3. Pastikan **tidak ada token asli** ikut tersimpan (kosongkan `Current value` variabel `token`/`xsrf_token` bila perlu sebelum export).
4. Commit:
   ```sh
   git add docs/postman/SIMAKIN-API-Collection.json docs/postman/SIMAKIN-Local.postman_environment.json
   git commit -m "[BE] docs: postman collection autentikasi"
   ```

---

## 7. Troubleshooting

| Gejala | Penyebab / Solusi |
|---|---|
| Login web 419 / tidak dapat cookie session | `Origin` ≠ `SANCTUM_STATEFUL_DOMAINS`. Pastikan `Origin: {{frontend_url}}` = nilai `SANCTUM_STATEFUL_DOMAINS`. |
| Login web sukses tapi `GET /user` web 401 | Cookie tidak tersimpan → cek Postman cookie jar, jalankan `GET Fetch CSRF Cookie` lebih dulu. |
| 401 di request yang butuh token | Jalankan `POST Login (Mobile)` (atau 4.1) agar `{{token}}` terisi. |
| Register 422 email duplikat | Body harus memakai email dinamis `{{$timestamp}}`. |
| Change password sukses lalu request lain 401 | Token lain dicabut. Jalankan `3.7`/re-login, atau letakkan change password di akhir. |
| Register / 4.6 / 4.7 → 419 padahal sudah login web | Token CSRF basi setelah `POST Login (Web)` (`session()->regenerate()`). Jalankan `GET Fetch CSRF Cookie (Refresh)` (`3.2b`) setelah login. |
| `{{xsrf_token}}` kosong | Jalankan `GET Fetch CSRF Cookie` lebih dulu, atau cek cookie domain (`localhost`). |

---

## 8. Checklist (Definition of Done)

- [ ] Environment `SIMAKIN Local` dibuat (termasuk `frontend_url`).
- [ ] Collection `SIMAKIN API` + folder `01. Authentication` & `99. Test Scenarios` dibuat.
- [ ] 9 request folder `01` selesai (Fetch CSRF, Login Web, Fetch CSRF (Refresh), Login Mobile, Get User, Register, Change Password, Change Password Kembalikan, Logout).
- [ ] 11 request folder `99` selesai.
- [ ] Setiap request punya Authorization/Headers, Tests, Documentation, dan Examples.
- [ ] Collection Runner: **semua test hijau**.
- [ ] Export collection + environment ke `docs/postman/`.
- [ ] Commit (dilakukan sendiri).
