# Playbook Eksekusi Postman — Data Master (Sprint 1)

Dokumen kerja untuk **menambahkan folder 03–08 ke collection `SIMAKIN API`** (GUI Postman) bagi 32 endpoint data master yang dikerjakan di Sprint 1. Isi di sini sudah siap-tempel: headers, body, *Pre-request Script*, *Tests*, tab *Documentation*, dan *Examples*.

Panduan konsep/umum: `.agents/tech-specs/2026/004-postman-collection-guide.md`.
Pola autentikasi (folder 01): `.agents/tasks/postman-auth-execution.md`.
Rencana sprint: `.agents/tasks/sprint-1-master-data-plan.md`.

**Ruang lingkup:** 6 folder baru (03–08) + skenario error di folder `99. Test Scenarios`.

| Folder | Requests |
|---|---|
| 03. Master - Jurusan | 6 (+1 bootstrap login admin) |
| 04. Master - Kompetensi | 5 |
| 05. Master - Gelombang | 5 |
| 06. Master - Perusahaan | 7 |
| 07. Master - Sikap | 5 |
| 08. Master - Waktu Absen | 4 |

> **Keputusan auth:** semua request master memakai **Bearer token admin** (`{{admin_token}}`) — bukan session web — supaya Collection Runner sederhana (tanpa CSRF dance). Login admin via header `X-Client: mobile` mengembalikan token untuk role apa pun.

---

## 0. Prasyarat

1. Folder `01. Authentication` collection `SIMAKIN API` sudah selesai (lihat `postman-auth-execution.md`).
2. Backend jalan + database ter-seed:
   ```sh
   php artisan serve
   php artisan migrate --seed
   ```
3. Seeder minimal berisi: role, user admin/guru/siswa, **10 jurusan** (Sprint 1), gelombang, sikap, waktu absen, perusahaan + pivot.
4. Environment `SIMAKIN Local` aktif.

### 0.1 Tambah variables environment

| Variable | Initial Value | Keterangan |
|---|---|---|
| `admin_token` | _(kosong)_ | Diisi otomatis saat `POST Login (Admin)` |
| `jurusan_id` | _(kosong)_ | ID jurusan uji terakhir (dibuat via POST) |
| `jurusan_seed_id` | `1` | ID jurusan hasil seeder (urutan `JurusanSeeder`) |
| `perusahaan_seed_id` | `1` | ID perusahaan hasil seeder (untuk skenario detach 404 di folder 99) |
| `kompetensi_id` | _(kosong)_ | ID kompetensi uji terakhir |
| `gelombang_id` | _(kosong)_ | ID gelombang uji terakhir |
| `perusahaan_id` | _(kosong)_ | ID perusahaan uji terakhir |
| `sikap_id` | _(kosong)_ | ID sikap uji terakhir |
| `waktu_absen_id` | _(kosong)_ | ID waktu absen uji terakhir |

### 0.2 Standar headers (berlaku untuk SEMUA request 03–08)

Kecuali dinyatakan lain, setiap request memakai:

```
Accept: application/json
Authorization: Bearer {{admin_token}}
```

Tidak ada header `Origin` / `X-XSRF-TOKEN` (ini alur token, bukan session).

### 0.3 Template Tests (salin sesuai nama template)

**`T-Create`** — untuk semua POST create (ganti `*_id`):
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
    pm.environment.set("jurusan_id", json.data.id); // GANTI nama variable per resource
}
```

**`T-Create-OK`** — alias `T-Create` tanpa baris `pm.environment.set` (untuk request create yang ID-nya tidak dipakai lanjutan).

**`T-List`**:
```javascript
pm.test("Status 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Response memiliki data array + meta", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("data");
    pm.expect(json.data).to.be.an("array");
    pm.expect(json).to.have.property("meta");
});
```

**`T-Detail`** (butuh `{{..._id}}` terisi):
```javascript
pm.test("Status 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Data sesuai ID", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("data");
    pm.expect(String(json.data.id)).to.eql(pm.environment.get("jurusan_id")); // GANTI variable
});
```

**`T-Update`**:
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

**`T-Delete`**:
```javascript
pm.test("Status 200 OK", function () {
    pm.response.to.have.status(200);
});

pm.test("Response berisi message", function () {
    pm.expect(pm.response.json()).to.have.property("message");
});
```

**`T-Error-422`** + properti errors (contoh `nama`):
```javascript
pm.test("Validasi gagal (422)", function () {
    pm.response.to.have.status(422);
});

pm.test("Ada errors.nama", function () {
    pm.expect(pm.response.json().errors).to.have.property("nama");
});
```

**`T-Error-403`**:
```javascript
pm.test("Status 403 Forbidden", function () {
    pm.response.to.have.status(403);
});
```

**`T-Error-401`**:
```javascript
pm.test("Status 401 Unauthenticated", function () {
    pm.response.to.have.status(401);
});
```

---

## 1. Folder `03. Master - Jurusan`

Buat request **sesuai urutan di bawah** (urutan penting untuk Collection Runner).

### 1.1 `POST Login (Admin)`

Bootstrap token admin untuk seluruh folder 03–08.

- **Method/URL:** `POST {{base_url}}/login`
- **Headers:**
  ```
  Accept: application/json
  X-Client: mobile
  ```
- **Body** (raw → JSON):
  ```json
  { "username": "{{admin_username}}", "password": "{{admin_password}}" }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Mengembalikan token admin", function () {
      pm.expect(json).to.have.property("token");
      pm.expect(json.user.role_id).to.eql(1);
  });

  if (json.token) {
      pm.environment.set("admin_token", json.token);
  }
  ```
- **Documentation (tab):**
  ```markdown
  ## Deskripsi
  Login admin via mobile (X-Client: mobile) untuk mendapatkan Bearer token.
  Token dipakai semua request folder 03-08 (Data Master).

  ## Error
  - 422 Kredensial tidak valid
  ```
- **Example:** `Success (200)`:
  ```json
  {
    "user": { "id": 1, "name": "Administrator", "username": "admin", "role_id": 1 },
    "token": "1|xxxxxxxx"
  }
  ```

---

### 1.2 `GET List Jurusan`

- **Method/URL:** `{{base_url}}/jurusan`
- **Headers:** standar (§0.2)
- **Query Params** (opsional): `search` — pencarian nama/kode
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List jurusan. Terbuka untuk semua role login.

  ## Query
  | Param | Type | Keterangan |
  |---|---|---|
  | search | string | Filter nama/kode (opsional) |

  ## Response
  - 200: `{ data: [...], links, meta }` (paginated, 20/halaman)
  ```
- **Example:** `Success (200)`:
  ```json
  {
    "data": [
      { "id": 1, "nama": "Teknik Jaringan Komputer dan Telekomunikasi", "kode": "TJKT" },
      { "id": 2, "nama": "Desain Komunikasi Visual", "kode": "DKV" }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 10 }
  }
  ```

---

### 1.3 `POST Create Jurusan`

- **Method/URL:** `{{base_url}}/jurusan`
- **Headers:** standar
- **Body** (raw → JSON) — nama unik via timestamp agar aman di-run ulang:
  ```json
  {
    "nama": "Teknik Uji {{$timestamp}}",
    "kode": "UJ{{$timestamp}}"
  }
  ```
- **Tests:** `T-Create` (simpan ke `jurusan_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat jurusan baru. Admin only.

  ## Request Body
  | Field | Type | Required | Maks |
  |---|---|---|---|
  | nama | string | Ya | 100 |
  | kode | string | Tidak | 20 |

  ## Error
  - 401 belum login / 403 bukan admin
  - 422 `nama` kosong atau > 100 karakter
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Jurusan berhasil disimpan.",
      "data": { "id": 11, "nama": "Teknik Uji 1760123456789", "kode": "UJ1760123456789" }
    }
    ```
  - `Validation Error (422)`:
    ```json
    { "message": "The nama field is required.", "errors": { "nama": ["The nama field is required."] } }
    ```

---

### 1.4 `GET Get Jurusan Detail`

- **Method/URL:** `{{base_url}}/jurusan/{{jurusan_id}}`
- **Headers:** standar
- **Tests:** `T-Detail` (variabel `jurusan_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail satu jurusan. Terbuka untuk semua role login.

  ## Error
  - 404 ID tidak ditemukan
  ```
- **Example:** `Success (200)`:
  ```json
  { "data": { "id": 11, "nama": "Teknik Uji 1760123456789", "kode": "UJ1760123456789" } }
  ```

---

### 1.5 `PUT Update Jurusan`

- **Method/URL:** `{{base_url}}/jurusan/{{jurusan_id}}`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  { "nama": "Teknik Uji Updated {{$timestamp}}", "kode": "UJD" }
  ```
- **Tests:** `T-Update`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update jurusan. Admin only. Body sama dengan Create (semua field boleh dikirim).

  ## Error
  - 403 bukan admin, 404 tidak ditemukan, 422 validasi
  ```
- **Example:** `Success (200)`:
  ```json
  {
    "message": "Jurusan berhasil diperbarui.",
    "data": { "id": 11, "nama": "Teknik Uji Updated 1760123456789", "kode": "UJD" }
  }
  ```

---

### 1.6 `GET List Kompetensi per Jurusan`

- **Method/URL:** `{{base_url}}/jurusan/{{jurusan_id}}/kompetensi`
- **Headers:** standar
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  pm.test("Response memiliki data array", function () {
      const json = pm.response.json();
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.be.an("array");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  List kompetensi milik satu jurusan (route nested).

  ## Catatan
  - Jurusan uji ({{jurusan_id}}) belum punya kompetensi → `data: []` (wajar).
  - Untuk data terisi, coba manual dengan `{{jurusan_seed_id}}` (jurusan seeder, berisi 5+ kompetensi).

  ## Error
  - 404 jurusan tidak ditemukan
  ```
- **Example:** `Success (200)`:
  ```json
  { "data": [ { "id": 1, "jurusan_id": 1, "nama": "Instalasi Jaringan Kabel Terstruktur", "kode": "TJKT-01" } ] }
  ```

---

### 1.7 `DELETE Delete Jurusan`

- **Method/URL:** `{{base_url}}/jurusan/{{jurusan_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus jurusan. Admin only. Jurusan dengan data kompetensi anak akan ikut terhapus (FK CASCADE).

  ## Error
  - 403 bukan admin, 404 tidak ditemukan
  - 422 data masih direferensikan tabel lain (FK RESTRICT) — `message` dijelaskan server
  ```
- **Example:** `Success (200)`:
  ```json
  { "message": "Jurusan berhasil dihapus." }
  ```

---

## 2. Folder `04. Master - Kompetensi`

> **Catatan urutan:** semua request di folder ini memakai `{{jurusan_seed_id}}` (jurusan hasil seeder, default `1`) karena `{{jurusan_id}}` dari folder 03 sudah dihapus (beserta cascade).

### 2.1 `GET List Kompetensi`

- **Method/URL:** `{{base_url}}/kompetensi`
- **Headers:** standar
- **Query Params** (opsional): `jurusan_id`, `search`
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List kompetensi. Terbuka untuk semua role login.

  ## Query
  | Param | Type | Keterangan |
  |---|---|---|
  | jurusan_id | integer | Filter per jurusan |
  | search | string | Filter nama/kode |
  ```
- **Example:** `Success (200)` — `GET {{base_url}}/kompetensi?jurusan_id={{jurusan_seed_id}}` (default jurusan 1 = DPIB, 5 kompetensi):
  ```json
  {
    "data": [
      {
        "id": 1,
        "jurusan_id": 1,
        "nama": "Gambar Kerja Bangunan",
        "kode": "DPIB-01",
        "jurusan": {
          "id": 1,
          "nama": "Desain Permodelan dan Informasi Bangunan",
          "kode": "DPIB"
        }
      },
      {
        "id": 5,
        "jurusan_id": 1,
        "nama": "Pengawasan Pelaksanaan Gambar Kerja",
        "kode": "DPIB-05",
        "jurusan": {
          "id": 1,
          "nama": "Desain Permodelan dan Informasi Bangunan",
          "kode": "DPIB"
        }
      }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": null },
    "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 5 }
  }
  ```

---

### 2.2 `POST Create Kompetensi`

- **Method/URL:** `{{base_url}}/kompetensi`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "jurusan_id": {{jurusan_seed_id}},
    "nama": "Kompetensi Uji {{$timestamp}}",
    "kode": "KU-{{$timestamp}}"
  }
  ```
- **Tests:** `T-Create` (simpan ke `kompetensi_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat kompetensi baru. Admin only. `jurusan_id` harus existing.

  ## Request Body
  | Field | Type | Required | Maks |
  |---|---|---|---|
  | jurusan_id | integer | Ya | - (exists) |
  | nama | string | Ya | 255 |
  | kode | string | Tidak | 50 |

  ## Error
  - 422 `jurusan_id` tidak valid / `nama` kosong
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Kompetensi berhasil disimpan.",
      "data": { "id": 51, "jurusan_id": 1, "nama": "Kompetensi Uji 1760123456789", "kode": "KU-1760123456789" }
    }
    ```

---

### 2.3 `GET Get Kompetensi Detail`

- **Method/URL:** `{{base_url}}/kompetensi/{{kompetensi_id}}`
- **Headers:** standar
- **Tests:** `T-Detail` (variabel `kompetensi_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail satu kompetensi.

  ## Error
  - 404 tidak ditemukan
  ```

---

### 2.4 `PUT Update Kompetensi`

- **Method/URL:** `{{base_url}}/kompetensi/{{kompetensi_id}}`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "jurusan_id": {{jurusan_seed_id}},
    "nama": "Kompetensi Uji Updated {{$timestamp}}"
  }
  ```
- **Tests:** `T-Update`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update kompetensi. Admin only.
  ```

---

### 2.5 `DELETE Delete Kompetensi`

- **Method/URL:** `{{base_url}}/kompetensi/{{kompetensi_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus kompetensi. Admin only.

  ## Error
  - 422 kompetensi masih direferensikan jurnal/penilaian (FK RESTRICT) — muncul di Sprint 5+
  ```

---

## 3. Folder `05. Master - Gelombang`

### 3.1 `GET List Gelombang`

- **Method/URL:** `{{base_url}}/gelombang`
- **Headers:** standar
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List gelombang PKL. Terbuka untuk semua role login.
  ```

---

### 3.2 `POST Create Gelombang`

- **Method/URL:** `{{base_url}}/gelombang`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "tahun_ajaran": "2028/2029",
    "nama": "Gelombang Uji {{$timestamp}}",
    "daftar_mulai": "2028-11-01",
    "daftar_selesai": "2028-11-30",
    "praktik_mulai": "2029-01-05",
    "praktik_selesai": "2029-06-30"
  }
  ```
- **Tests:** `T-Create` (simpan ke `gelombang_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat gelombang PKL baru. Admin only.

  ## Request Body
  | Field | Type | Required | Catatan |
  |---|---|---|---|
  | tahun_ajaran | string | Ya | Maks 20, format "2026/2027" |
  | nama | string | Ya | Maks 100 |
  | daftar_mulai | date | Ya | YYYY-MM-DD |
  | daftar_selesai | date | Ya | >= daftar_mulai |
  | praktik_mulai | date | Ya | YYYY-MM-DD |
  | praktik_selesai | date | Ya | >= praktik_mulai |

  ## Business Rules
  - daftar_selesai tidak boleh sebelum daftar_mulai
  - praktik_selesai tidak boleh sebelum praktik_mulai

  ## Error
  - 422 tanggal tidak berurutan (`errors.daftar_selesai` / `errors.praktik_selesai`)
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Gelombang berhasil disimpan.",
      "data": {
        "id": 3, "tahun_ajaran": "2028/2029", "nama": "Gelombang Uji 1760123456789",
        "daftar_mulai": "2028-11-01", "daftar_selesai": "2028-11-30",
        "praktik_mulai": "2029-01-05", "praktik_selesai": "2029-06-30"
      }
    }
    ```
  - `Validation Error (422)`:
    ```json
    {
      "message": "The daftar selesai must be a date after or equal to daftar mulai.",
      "errors": { "daftar_selesai": ["The daftar selesai must be a date after or equal to daftar mulai."] }
    }
    ```

---

### 3.3 `GET Get Gelombang Detail`

- **Method/URL:** `{{base_url}}/gelombang/{{gelombang_id}}`
- **Headers:** standar
- **Tests:** `T-Detail` (variabel `gelombang_id`)

---

### 3.4 `PUT Update Gelombang`

- **Method/URL:** `{{base_url}}/gelombang/{{gelombang_id}}`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "tahun_ajaran": "2028/2029",
    "nama": "Gelombang Uji Updated {{$timestamp}}",
    "daftar_mulai": "2028-11-01",
    "daftar_selesai": "2028-12-15",
    "praktik_mulai": "2029-01-05",
    "praktik_selesai": "2029-06-30"
  }
  ```
- **Tests:** `T-Update`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update gelombang. Admin only. Validasi tanggal sama seperti Create.
  ```

---

### 3.5 `DELETE Delete Gelombang`

- **Method/URL:** `{{base_url}}/gelombang/{{gelombang_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus gelombang. Admin only.

  ## Error
  - 422 gelombang masih direferensikan siswa/penempatan (FK RESTRICT) — muncul di Sprint 2+
  ```

---

## 4. Folder `06. Master - Perusahaan`

### 4.1 `GET List Perusahaan`

- **Method/URL:** `{{base_url}}/perusahaan`
- **Headers:** standar
- **Query Params** (opsional): `jurusan_id`, `search`
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List perusahaan/industri. Terbuka untuk semua role login.

  ## Query
  | Param | Type | Keterangan |
  |---|---|---|
  | jurusan_id | integer | Hanya perusahaan yang menerima jurusan tsb |
  | search | string | Filter nama/alamat |

  ## Catatan
  Filter `kuota_tersedia` diuji penuh di Sprint 3 (butuh data penempatan).
  ```

---

### 4.2 `POST Create Perusahaan`

- **Method/URL:** `{{base_url}}/perusahaan`
- **Headers:** standar
- **Body** (raw → JSON) — `jurusan_ids` memakai ID jurusan seeder:
  ```json
  {
    "nama": "PT Uji {{$timestamp}}",
    "alamat": "Jl. Uji No. 1, Magelang",
    "latitude": -7.49160000,
    "longitude": 110.21800000,
    "pimpinan": "Budi Uji",
    "telp": "0293123456",
    "email": "hrd.uji{{$timestamp}}@example.com",
    "deskripsi": "Perusahaan uji Collection Runner",
    "kuota": 10,
    "jurusan_ids": [1, 2]
  }
  ```
- **Tests:** `T-Create` (simpan ke `perusahaan_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat perusahaan baru + attach jurusan sekaligus. Admin only.

  ## Request Body
  | Field | Type | Required | Catatan |
  |---|---|---|---|
  | nama | string | Ya | Maks 255 |
  | alamat | text | Ya | |
  | latitude | decimal | Tidak | -90..90 |
  | longitude | decimal | Tidak | -180..180 |
  | pimpinan | string | Tidak | Maks 255 |
  | telp | string | Tidak | Maks 20 |
  | email | string | Tidak | Format email |
  | deskripsi | text | Tidak | |
  | kuota | integer | Tidak | >= 0 |
  | jurusan_ids | array[int] | Tidak | Semua harus exists |

  ## Error
  - 422 `jurusan_ids.0` tidak valid bila ada ID jurusan tak ditemukan
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Perusahaan berhasil disimpan.",
      "data": {
        "id": 13,
        "nama": "PT Uji 1760123456789",
        "alamat": "Jl. Uji No. 1, Magelang",
        "latitude": -7.4916, "longitude": 110.218,
        "pimpinan": "Budi Uji", "telp": "0293123456",
        "email": "hrd.uji1760123456789@example.com",
        "deskripsi": "Perusahaan uji Collection Runner",
        "kuota": 10,
        "jurusan": [
          { "id": 1, "nama": "Teknik Jaringan Komputer dan Telekomunikasi", "kode": "TJKT" },
          { "id": 2, "nama": "Desain Komunikasi Visual", "kode": "DKV" }
        ]
      }
    }
    ```

---

### 4.3 `GET Get Perusahaan Detail`

- **Method/URL:** `{{base_url}}/perusahaan/{{perusahaan_id}}`
- **Headers:** standar
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  pm.test("Data sesuai ID & berisi jurusan", function () {
      const json = pm.response.json();
      pm.expect(String(json.data.id)).to.eql(pm.environment.get("perusahaan_id"));
      pm.expect(json.data).to.have.property("jurusan");
      pm.expect(json.data.jurusan).to.be.an("array").that.is.not.empty;
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Detail perusahaan termasuk daftar jurusan yang diterima (eager load).

  ## Error
  - 404 tidak ditemukan
  ```

---

### 4.4 `PUT Update Perusahaan`

- **Method/URL:** `{{base_url}}/perusahaan/{{perusahaan_id}}`
- **Headers:** standar
- **Body** (raw JSON):
  ```json
  {
    "nama": "PT Uji Updated {{$timestamp}}",
    "alamat": "Jl. Uji No. 2, Magelang",
    "latitude": -7.49160000,
    "longitude": 110.21800000,
    "pimpinan": "Budi Uji",
    "telp": "0293123456",
    "email": "hrd.uji@example.com",
    "deskripsi": "Perusahaan uji (updated)",
    "kuota": 12,
    "jurusan_ids": [1, 3]
  }
  ```
- **Tests:** `T-Update`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update perusahaan. Admin only. `jurusan_ids` (bila dikirim) menggantikan seluruh attach (sync).

  ## Error
  - 422 validasi / `jurusan_ids` invalid
  ```

---

### 4.5 `POST Attach Jurusan`

- **Method/URL:** `{{base_url}}/perusahaan/{{perusahaan_id}}/jurusan`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  { "jurusan_id": 2 }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  const json = pm.response.json();

  pm.test("Response berisi message & data.jurusan", function () {
      pm.expect(json).to.have.property("message");
      pm.expect(json.data).to.have.property("jurusan");
  });

  pm.test("jurusan_id 2 sudah terpasang", function () {
      const ids = json.data.jurusan.map(j => j.id);
      pm.expect(ids).to.include(2);
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Tambah satu jurusan ke perusahaan (attach). Admin only. Idempotent — jurusan yang sudah terpasang tidak diduplikasi.

  ## Request Body
  | Field | Type | Required |
  |---|---|---|
  | jurusan_id | integer | Ya |

  ## Error
  - 404 perusahaan tidak ditemukan
  - 422 `jurusan_id` tidak exists
  ```
- **Example:** `Success (200)`:
  ```json
  {
    "message": "Jurusan berhasil ditambahkan ke perusahaan.",
    "data": {
      "id": 13,
      "nama": "PT Uji Updated 1760123456789",
      "jurusan": [ { "id": 1, "nama": "..." }, { "id": 2, "nama": "..." } ]
    }
  }
  ```

---

### 4.6 `DELETE Detach Jurusan`

- **Method/URL:** `{{base_url}}/perusahaan/{{perusahaan_id}}/jurusan/2`
- **Headers:** standar
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () {
      pm.response.to.have.status(200);
  });

  pm.test("Response berisi message", function () {
      pm.expect(pm.response.json()).to.have.property("message");
  });
  ```
- **Documentation:**
  ```markdown
  ## Deskripsi
  Lepas satu jurusan dari perusahaan (detach). Admin only.

  ## Error
  - 404 perusahaan tidak ditemukan ATAU jurusan tidak terpasang di perusahaan tsb
  ```
- **Example:** `Success (200)`:
  ```json
  { "message": "Jurusan berhasil dilepas dari perusahaan." }
  ```

---

### 4.7 `DELETE Delete Perusahaan`

- **Method/URL:** `{{base_url}}/perusahaan/{{perusahaan_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus perusahaan. Admin only. Relasi pivot ikut terhapus (CASCADE).

  ## Error
  - 422 masih direferensikan pengajuan/penempatan (Sprint 3+)
  ```

---

## 5. Folder `07. Master - Sikap`

### 5.1 `GET List Sikap`

- **Method/URL:** `{{base_url}}/sikap`
- **Headers:** standar
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List master sikap/soft skill untuk penilaian. Terbuka untuk semua role login.
  ```

---

### 5.2 `POST Create Sikap`

- **Method/URL:** `{{base_url}}/sikap`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "nama": "Sikap Uji {{$timestamp}}",
    "deskripsi": "Sikap hasil uji Collection Runner"
  }
  ```
- **Tests:** `T-Create` (simpan ke `sikap_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat master sikap baru. Admin only.

  ## Request Body
  | Field | Type | Required | Maks |
  |---|---|---|---|
  | nama | string | Ya | 100 |
  | deskripsi | text | Tidak | - |
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Sikap berhasil disimpan.",
      "data": { "id": 11, "nama": "Sikap Uji 1760123456789", "deskripsi": "Sikap hasil uji Collection Runner" }
    }
    ```

---

### 5.3 `GET Get Sikap Detail`

- **Method/URL:** `{{base_url}}/sikap/{{sikap_id}}`
- **Headers:** standar
- **Tests:** `T-Detail` (variabel `sikap_id`)

---

### 5.4 `PUT Update Sikap`

- **Method/URL:** `{{base_url}}/sikap/{{sikap_id}}`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  { "nama": "Sikap Uji Updated {{$timestamp}}", "deskripsi": "Updated" }
  ```
- **Tests:** `T-Update`

---

### 5.5 `DELETE Delete Sikap`

- **Method/URL:** `{{base_url}}/sikap/{{sikap_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Hapus master sikap. Admin only.

  ## Error
  - 422 masih direferensikan penilaian_sikap (Sprint 6+)
  ```

---

## 6. Folder `08. Master - Waktu Absen`

> Folder ini **hanya 4 request** (tanpa `GET Detail`) — sesuai `003-api-endpoints.md` §8.

### 6.1 `GET List Waktu Absen`

- **Method/URL:** `{{base_url}}/waktu-absen`
- **Headers:** standar
- **Tests:** `T-List`
- **Documentation:**
  ```markdown
  ## Deskripsi
  List konfigurasi jam kerja PKL. Terbuka untuk semua role login.
  ```

---

### 6.2 `POST Create Waktu Absen`

- **Method/URL:** `{{base_url}}/waktu-absen`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  {
    "mulai": "07:30",
    "selesai": "15:30",
    "keterangan": "Jam kerja uji {{$timestamp}}"
  }
  ```
- **Tests:** `T-Create` (simpan ke `waktu_absen_id`)
- **Documentation:**
  ```markdown
  ## Deskripsi
  Buat konfigurasi waktu absen baru. Admin only.

  ## Request Body
  | Field | Type | Required | Format |
  |---|---|---|---|
  | mulai | string | Ya | HH:mm (contoh "08:00") |
  | selesai | string | Ya | HH:mm, harus setelah `mulai` |
  | keterangan | string | Tidak | Maks 255 |

  ## Error
  - 422 `selesai` tidak setelah `mulai` (`errors.selesai`)
  - 422 format jam salah
  ```
- **Examples:**
  - `Success (201)`:
    ```json
    {
      "message": "Waktu absen berhasil disimpan.",
      "data": { "id": 2, "mulai": "07:30:00", "selesai": "15:30:00", "keterangan": "Jam kerja uji 1760123456789" }
    }
    ```
  - `Validation Error (422)`:
    ```json
    {
      "message": "The selesai must be a date after mulai.",
      "errors": { "selesai": ["The selesai must be a date after mulai."] }
    }
    ```

---

### 6.3 `PUT Update Waktu Absen`

- **Method/URL:** `{{base_url}}/waktu-absen/{{waktu_absen_id}}`
- **Headers:** standar
- **Body** (raw → JSON):
  ```json
  { "mulai": "08:00", "selesai": "16:00", "keterangan": "Jam kerja normal (updated)" }
  ```
- **Tests:** `T-Update`
- **Documentation:**
  ```markdown
  ## Deskripsi
  Update waktu absen. Admin only. Validasi jam sama seperti Create.
  ```

---

### 6.4 `DELETE Delete Waktu Absen`

- **Method/URL:** `{{base_url}}/waktu-absen/{{waktu_absen_id}}`
- **Headers:** standar
- **Tests:** `T-Delete`

---

## 7. Tambahan Folder `99. Test Scenarios`

Tambahkan request berikut (prefix `[Master]`). **Semua adalah skenario error** — assert kode error, bukan 200/201. `{{token}}` = token siswa hasil `POST Login (Mobile)` folder 01 (jalankan 3.3/4.1 dulu bila kosong).

### 7.1 `[Master] POST Create Jurusan - Tanpa Nama`

- `POST {{base_url}}/jurusan`, headers standar admin, body `{ "kode": "X1" }`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.nama", function () {
      pm.expect(pm.response.json().errors).to.have.property("nama");
  });
  ```

### 7.2 `[Master] POST Create Jurusan - Sebagai Siswa`

- `POST {{base_url}}/jurusan`, headers: `Accept`, `Authorization: Bearer {{token}}` (siswa)
- Body bebas (boleh statis `{ "nama": "Uji 403" }`)
- **Tests:**
  ```javascript
  pm.test("Status 403", function () { pm.response.to.have.status(403); });
  ```

### 7.3 `[Master] GET List Jurusan - Tanpa Token`

- `GET {{base_url}}/jurusan`, headers hanya `Accept: application/json`
- **Tests:**
  ```javascript
  pm.test("Status 401", function () { pm.response.to.have.status(401); });
  ```

### 7.4 `[Master] POST Create Gelombang - Tanggal Terbalik`

- `POST {{base_url}}/gelombang`, headers standar admin, body:
  ```json
  {
    "tahun_ajaran": "2028/2029",
    "nama": "Gelombang Salah Tanggal",
    "daftar_mulai": "2028-11-30",
    "daftar_selesai": "2028-11-01",
    "praktik_mulai": "2029-01-05",
    "praktik_selesai": "2029-06-30"
  }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.daftar_selesai", function () {
      pm.expect(pm.response.json().errors).to.have.property("daftar_selesai");
  });
  ```

### 7.5 `[Master] POST Create Kompetensi - Jurusan Tidak Ada`

- `POST {{base_url}}/kompetensi`, headers standar admin, body:
  ```json
  { "jurusan_id": 99999, "nama": "Kompetensi Yatim" }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.jurusan_id", function () {
      pm.expect(pm.response.json().errors).to.have.property("jurusan_id");
  });
  ```

### 7.6 `[Master] POST Create Perusahaan - Jurusan ID Invalid`

- `POST {{base_url}}/perusahaan`, headers standar admin, body:
  ```json
  { "nama": "PT Tanpa Jurusan", "alamat": "Magelang", "jurusan_ids": [99999] }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.jurusan_ids", function () {
      pm.expect(pm.response.json().errors).to.have.property("jurusan_ids");
  });
  ```

### 7.7 `[Master] DELETE Detach Jurusan - Tidak Terpasang`

- `DELETE {{base_url}}/perusahaan/{{perusahaan_seed_id}}/jurusan/99999`
  - `perusahaan_seed_id`: set manual di environment = ID perusahaan hasil seeder (atau ganti literal `1` bila seeder membuat perusahaan pertama id 1).
- **Tests:**
  ```javascript
  pm.test("Status 404", function () { pm.response.to.have.status(404); });
  ```

### 7.8 `[Master] POST Create Waktu Absen - Selesai Sebelum Mulai`

- `POST {{base_url}}/waktu-absen`, headers standar admin, body:
  ```json
  { "mulai": "16:00", "selesai": "08:00", "keterangan": "Terbalik" }
  ```
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.selesai", function () {
      pm.expect(pm.response.json().errors).to.have.property("selesai");
  });
  ```

### 7.9 `[Master] POST Create Sikap - Tanpa Nama`

- `POST {{base_url}}/sikap`, headers standar admin, body `{ "deskripsi": "Tanpa nama" }`
- **Tests:**
  ```javascript
  pm.test("Status 422", function () { pm.response.to.have.status(422); });
  pm.test("Ada errors.nama", function () {
      pm.expect(pm.response.json().errors).to.have.property("nama");
  });
  ```

> Tambahkan variable environment `perusahaan_seed_id` (Initial Value `1`) untuk skenario 7.7.

---

## 8. Jalankan Collection Runner

1. Pastikan `{{token}}` (siswa) terisi — jalankan folder `01. Authentication` dulu bila belum.
2. Buka collection **SIMAKIN API** → **Run**.
3. Environment: **SIMAKIN Local**. Iteration `1`, Delay `100 ms`.
4. **Select folders** berurutan: `03. Master - Jurusan` → `04` → `05` → `06` → `07` → `08` → `99. Test Scenarios`.
5. **Run**. Semua test harus **PASS**.
6. Urutan penting: `1.1 POST Login (Admin)` harus jalan paling dulu di folder 03 (menyiapkan `{{admin_token}}`); request ID-dependent harus setelah create-nya.

> **Run ulang aman:** semua create memakai nama ber-`{{$timestamp}}` sehingga tidak bentrok. Data uji menumpuk di DB lokal — bersihkan bila perlu dengan `php artisan migrate:fresh --seed`.

---

## 9. Export & Commit (dilakukan sendiri)

1. Export collection: **⋯** → **Export** → **Collection v2.1** → timpa `docs/postman/SIMAKIN-API-Collection.json`.
2. Export environment `SIMAKIN Local` → timpa `docs/postman/SIMAKIN-Local.postman_environment.json`.
3. Kosongkan `Current value` `admin_token` / `token` / `xsrf_token` sebelum export (jangan bocorkan token).
4. Commit:
   ```sh
   git add docs/postman/SIMAKIN-API-Collection.json docs/postman/SIMAKIN-Local.postman_environment.json
   git commit -m "[BE] docs: postman collection data master"
   ```

---

## 10. Troubleshooting

| Gejala | Penyebab / Solusi |
|---|---|
| Semua request 401 | `{{admin_token}}` kosong → jalankan `1.1 POST Login (Admin)` dulu, atau login ulang (token bisa terhapus bila user login lagi? tidak — token Sanctum persisten sampai logout/revoke) |
| 403 di semua write | Token dipakai bukan admin → pastikan login pakai `{{admin_username}}` |
| 404 di Detail/Update/Delete | `{{..._id}}` kosong/stale → jalankan request Create di folder yang sama lebih dulu |
| `4.6 DELETE Detach` 404 | Jurusan 2 sudah dilepas oleh run sebelumnya / tidak pernah attach → attach (4.5) dulu, atau pakai ID jurusan lain yang terpasang |
| 422 `jurusan_ids.0` | Ada ID di `jurusan_ids` tidak ada di DB → ganti dengan ID jurusan seeder (`{{jurusan_seed_id}}` dst.) |
| Create 422 email perusahaan duplikat | Tidak ada unique constraint email di spec — bila implementasi menambahkannya, pakai email ber-`{{$timestamp}}` (sudah demikian di body 4.2) |
| Runner berhenti di tengah | Cek request pertama gagal (biasanya 401 login admin) — runner melanjutkan request berikutnya tapi ID kosong → 404 berantai |
| Response list tanpa `meta` | Implementasi memakai `->get()` bukan `->paginate()` — laporkan, spec menentukan paginated 20/halaman |

---

## 11. Checklist (Definition of Done)

- [ ] Variables environment baru ditambahkan (§0.1).
- [ ] Folder `03` berisi 7 request (Login Admin + 6 Jurusan) — semua Tests/Documentation/Examples terisi.
- [ ] Folder `04` berisi 5 request Kompetensi.
- [ ] Folder `05` berisi 5 request Gelombang.
- [ ] Folder `06` berisi 7 request Perusahaan (termasuk attach/detach).
- [ ] Folder `07` berisi 5 request Sikap.
- [ ] Folder `08` berisi 4 request Waktu Absen (tanpa GET Detail).
- [ ] Folder `99` bertambah 9 skenario `[Master]` (§7).
- [ ] Collection Runner folder 03–08 + 99: **semua test hijau**.
- [ ] Export collection + environment ke `docs/postman/` (token dikosongkan).
- [ ] Commit (dilakukan sendiri).
