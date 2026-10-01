# API Docs untuk Fondasi

## Informasi Umum

| Item | Nilai |
|---|---|
| Variabel `base_url` | `http://localhost:8000/api` |
| URL CSRF cookie (khusus web) | `http://localhost:8000/sanctum/csrf-cookie` |
| Format data | JSON |

**role_id:** 1 (admin), 2 (guru), 3 (siswa)

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
  "email": "siswa@example.com",
  "password": "siswa123"
}
```

**Output:** `200 OK`

```json
{
  "user": {
    "id": 4,
    "name": "Siswa Teladan",
    "email": "siswa@example.com",
    "email_verified_at": "2026-09-30T04:02:08.000000Z",
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
  "email": "siswa@example.com",
  "email_verified_at": "2026-09-30T04:02:08.000000Z",
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
  "email": "guru@example.com",
  "password": "guru123"
}
```

**Output:** `200 OK`

```json
{
  "user": {
    "id": 3,
    "name": "Guru Pengajar",
    "email": "guru@example.com",
    "email_verified_at": "2026-09-30T04:02:07.000000Z",
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
  "email": "guru@example.com",
  "email_verified_at": "2026-09-30T04:02:07.000000Z",
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

## C. Tes Kasus Gagal

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
  "email": "siswa@example.com",
  "password": "asikinaja"
}
```

**Output:** `422 Unprocessable Entity`

```json
{
  "message": "Kredensial tidak valid.",
  "errors": {
    "email": [
      "Kredensial tidak valid."
    ]
  }
}
```

### 2. Login dengan email tidak terdaftar

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
  "email": "siswateladan@example.com",
  "password": "siswa123"
}
```

**Output:** `422 Unprocessable Entity`

```json
{
  "message": "Kredensial tidak valid.",
  "errors": {
    "email": [
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
  "message": "The email field is required. (and 1 more error)",
  "errors": {
    "email": [
      "The email field is required."
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