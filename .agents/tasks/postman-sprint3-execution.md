# Playbook Eksekusi Postman — Sprint 3 (Pengajuan & Penempatan PKL)

Dokumen kerja untuk **menambahkan folder 11, 12 ke collection `SIMAKIN API`** (GUI Postman) bagi endpoint Sprint 3. Isi siap-tempel: headers, body, *Pre-request Script*, *Tests*, tab *Documentation*, dan *Examples*.

**Panduan konsep/umum:** `.agents/tech-specs/2026/004-postman-collection-guide.md`  
**Pola autentikasi (folder 01):** `.agents/tasks/postman-auth-execution.md`  
**Playbook Sprint 2:** `.agents/tasks/postman-sprint2-execution.md`

**Ruang lingkup:** Folder `11. Pengajuan PKL`, `12. Lokasi PKL`, endpoint lanjutan `GET /api/guru/{id}/siswa-bimbingan`, `GET /api/siswa/{id}/lokasi-pkl`, `GET /api/dashboard/admin` + skenario di folder `99. Test Scenarios`.

| Folder | Requests | Keterangan |
|---|---|---|
| 11. Pengajuan PKL | 7 | List/detail/ajukan/approve/reject/bulk-approve/cancel |
| 12. Lokasi PKL | 6 | CRUD + update status |
| (lanjutan) | 3 | siswa-bimbingan, siswa lokasi-pkl, dashboard admin |

> **Keputusan auth:** Siswa memakai `{{siswa_token}}` untuk pengajuan/cancel. Admin/guru memakai `{{admin_token}}`/`{{guru_token}}` untuk approve/reject/list. `POST /api/pengajuan-pkl` memerlukan profil siswa sudah dikonfirmasi (`profil.confirmed`).

---

## 0. Prasyarat

1. Folder `01. Authentication` selesai (token admin/guru/siswa terisi).
2. Backend jalan + DB ter-seed (Sprint 3 seeder: pengajuan & lokasi PKL sample).
3. Environment `SIMAKIN Local` aktif dengan variabel: `admin_token`, `guru_token`, `siswa_token`, `siswa_id`, `guru_id`, `perusahaan_id`, `gelombang_id`, `pengajuan_id`, `lokasi_pkl_id`.
4. Ada siswa **sudah konfirmasi profil** (untuk pengajuan) dan siswa **belum konfirmasi** (untuk skenario gate).

### 0.1 Standar headers

**Admin/Guru:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}   (atau {{guru_token}})
```

**Siswa:**
```
Accept: application/json
Authorization: Bearer {{siswa_token}}
```

### 0.2 Template Tests
Gunakan `T-OK`, `T-Create`, `T-Error` dari `postman-sprint2-execution.md` §0.4.

---

## 1. Folder `11. Pengajuan PKL`

Buat request **sesuai urutan di bawah**.

### 1.1 `GET List Pengajuan PKL`

- **Method/URL:** `GET {{base_url}}/pengajuan-pkl`
- **Headers:** standar admin/guru (atau siswa)
- **Query Params (opsional):** `status`, `siswa_id`, `perusahaan_id`, `gelombang_id`, `page`
- **Tests:** `T-List`
- **Documentation:** List pengajuan (admin/guru semua; siswa hanya miliknya).

### 1.2 `GET Get Pengajuan Detail`

- **Method/URL:** `GET {{base_url}}/pengajuan-pkl/{{pengajuan_id}}`
- **Headers:** standar
- **Tests:** `T-Detail`
- **Documentation:** Detail pengajuan + relasi siswa/perusahaan/gelombang/verifier.

### 1.3 `POST Ajukan PKL (Siswa)`

- **Method/URL:** `POST {{base_url}}/pengajuan-pkl`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  { "perusahaan_id": {{perusahaan_id}}, "gelombang_id": {{gelombang_id}} }
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
      pm.environment.set("pengajuan_id", json.data.id);
  }
  ```
- **Documentation:** Siswa ajukan PKL. Wajib profil confirmed (gate `profil.confirmed`). Validasi gelombang aktif, jurusan sesuai, kuota, duplicate, placed.

### 1.4 `PUT Approve Pengajuan`

- **Method/URL:** `PUT {{base_url}}/pengajuan-pkl/{{pengajuan_id}}/approve`
- **Headers:** standar admin/guru
- **Body (raw → JSON):**
  ```json
  { "guru_pembimbing_id": {{guru_id}} }
  ```
- **Tests:** `T-OK`
- **Documentation:** Approve pengajuan + auto-create `lokasi_pkl` (status active) + notifikasi ke siswa.

### 1.5 `PUT Reject Pengajuan`

- **Method/URL:** `PUT {{base_url}}/pengajuan-pkl/{{pengajuan_id}}/reject`
- **Headers:** standar admin/guru
- **Body (raw → JSON):**
  ```json
  { "alasan_penolakan": "Kuota sudah penuh" }
  ```
- **Tests:** `T-OK`
- **Documentation:** Reject pengajuan (hanya status pending). Wajib `alasan_penolakan`.

### 1.6 `PUT Bulk Approve`

- **Method/URL:** `PUT {{base_url}}/pengajuan-pkl/bulk-approve`
- **Headers:** standar admin/guru
- **Body (raw → JSON):**
  ```json
  { "ids": [{{pengajuan_id}}], "guru_pembimbing_id": {{guru_id}} }
  ```
- **Tests:** `T-OK`
- **Documentation:** Bulk approve (array IDs). `bulk-approve` route terdaftar sebelum `{id}`.

### 1.7 `DELETE Cancel Pengajuan (Siswa)`

- **Method/URL:** `DELETE {{base_url}}/pengajuan-pkl/{{pengajuan_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Tests:** `T-OK`
- **Documentation:** Cancel pengajuan milik sendiri (hanya status pending).

---

## 2. Folder `12. Lokasi PKL`

### 2.1 `GET List Lokasi PKL`

- **Method/URL:** `GET {{base_url}}/lokasi-pkl`
- **Headers:** standar admin/guru
- **Query Params (opsional):** `status`, `siswa_id`, `guru_id`, `perusahaan_id`, `gelombang_id`, `search`, `page`
- **Tests:** `T-List`

### 2.2 `GET Get Lokasi PKL Detail`

- **Method/URL:** `GET {{base_url}}/lokasi-pkl/{{lokasi_pkl_id}}`
- **Headers:** standar
- **Tests:** `T-Detail`

### 2.3 `POST Create Lokasi PKL (Admin)`

- **Method/URL:** `POST {{base_url}}/lokasi-pkl`
- **Headers:** standar admin
- **Body (raw → JSON):**
  ```json
  {
    "siswa_id": {{siswa_id}},
    "perusahaan_id": {{perusahaan_id}},
    "guru_id": {{guru_id}},
    "gelombang_id": {{gelombang_id}},
    "catatan": "Penempatan manual"
  }
  ```
- **Tests:** `T-Create` (simpan ke `lokasi_pkl_id`)
- **Documentation:** Validasi siswa belum punya lokasi active → `SISWA_ALREADY_PLACED`.

### 2.4 `PUT Update Lokasi PKL`

- **Method/URL:** `PUT {{base_url}}/lokasi-pkl/{{lokasi_pkl_id}}`
- **Headers:** standar admin
- **Body (raw → JSON):**
  ```json
  { "guru_id": {{guru_id}}, "catatan": "Update catatan" }
  ```
- **Tests:** `T-Update`

### 2.5 `PUT Update Status Lokasi PKL`

- **Method/URL:** `PUT {{base_url}}/lokasi-pkl/{{lokasi_pkl_id}}/status`
- **Headers:** standar admin
- **Body (raw → JSON):**
  ```json
  { "status": "active" }
  ```
- **Tests:** `T-OK`
- **Documentation:** Flow: `pending` → `active`/`cancelled`; `active` → `completed`/`cancelled`.

### 2.6 `DELETE Delete Lokasi PKL`

- **Method/URL:** `DELETE {{base_url}}/lokasi-pkl/{{lokasi_pkl_id}}`
- **Headers:** standar admin
- **Tests:** `T-Delete`

---

## 3. Endpoint Lanjutan Sprint 2/3

### 3.1 `GET Siswa Bimbingan (Guru)`

- **Method/URL:** `GET {{base_url}}/guru/{{guru_id}}/siswa-bimbingan`
- **Headers:** standar admin/guru
- **Tests:** `T-List`-like (data array)
- **Documentation:** Daftar siswa bimbingan (status active). `presensi_hari_ini` & `jurnal_hari_ini` = null (Sprint 4/5).

### 3.2 `GET Lokasi PKL Siswa`

- **Method/URL:** `GET {{base_url}}/siswa/{{siswa_id}}/lokasi-pkl`
- **Headers:** standar (siswa miliknya pakai `{{siswa_token}}`)
- **Tests:** `T-Detail`-like
- **Documentation:** Lokasi PKL milik siswa.

### 3.3 `GET Dashboard Admin`

- **Method/URL:** `GET {{base_url}}/dashboard/admin`
- **Headers:** standar admin
- **Tests:**
  ```javascript
  pm.test("Status 200 OK", function () { pm.response.to.have.status(200); });
  const json = pm.response.json();
  pm.test("Struktur dashboard", function () {
      pm.expect(json).to.have.property("total_siswa");
      pm.expect(json).to.have.property("siswa_by_status");
      pm.expect(json).to.have.property("pengajuan_pending");
  });
  ```
- **Documentation:** Statistik admin (cache 10 menit). `presensi_hari_ini` placeholder.

---

## 4. Tambahan Folder `99. Test Scenarios`

Tambahkan skenario Sprint 3 berikut (prefix `[Sprint3]`):

| # | Request Name | Method/URL | Auth | Expected |
|---|---|---|---|---|
| TS-S3-01 | `[Sprint3][Pengajuan] POST Ajukan - Belum Konfirmasi (Forbidden Gate)` | `POST /pengajuan-pkl` | `{{siswa_token}}` (siswa belum konfirmasi) | `403 PROFIL_BELUM_KONFIRMASI` |
| TS-S3-02 | `[Sprint3][Pengajuan] POST Ajukan - Gelombang Tidak Aktif` | `POST /pengajuan-pkl` (gelombang tutup) | `{{siswa_token}}` | `422 GELOMBANG_NOT_ACTIVE` |
| TS-S3-03 | `[Sprint3][Pengajuan] POST Ajukan - Duplicate` | `POST /pengajuan-pkl` (2x) | `{{siswa_token}}` | `422 PENGAJUAN_DUPLICATE` |
| TS-S3-04 | `[Sprint3][Pengajuan] POST Ajukan - Jurusan Tidak Sesuai` | `POST /pengajuan-pkl` (perusahaan beda jurusan) | `{{siswa_token}}` | `422` |
| TS-S3-05 | `[Sprint3][Pengajuan] POST Ajukan - Kuota Penuh` | `POST /pengajuan-pkl` (kuota 0) | `{{siswa_token}}` | `422 PERUSAHAAN_KUOTA_PENUH` |
| TS-S3-06 | `[Sprint3][Pengajuan] POST Ajukan - Siswa Already Placed` | `POST /pengajuan-pkl` (siswa punya lokasi active) | `{{siswa_token}}` | `422 SISWA_ALREADY_PLACED` |
| TS-S3-07 | `[Sprint3][Pengajuan] DELETE Cancel - Non Pending` | `DELETE /pengajuan-pkl/{{pengajuan_id}}` (setelah approve) | `{{siswa_token}}` | `422` |
| TS-S3-08 | `[Sprint3][Pengajuan] DELETE Cancel - Bukan Pemilik` | `DELETE /pengajuan-pkl/{{pengajuan_id}}` | siswa lain token | `403` |
| TS-S3-09 | `[Sprint3][Lokasi] POST Create - Siswa Already Placed` | `POST /lokasi-pkl` (siswa punya active) | `{{admin_token}}` | `422 SISWA_ALREADY_PLACED` |
| TS-S3-10 | `[Sprint3][Lokasi] PUT Status - Transisi Invalid` | `PUT /lokasi-pkl/{{lokasi_pkl_id}}/status` body `{ "status": "active" }` (dari completed) | `{{admin_token}}` | `422` |
| TS-S3-11 | `[Sprint3][Lokasi] GET Siswa Lokasi - Siswa Lain (Forbidden)` | `GET /siswa/{{siswa_id}}/lokasi-pkl` | siswa lain token | `403` |
| TS-S3-12 | `[Sprint3][Dashboard] GET Dashboard - Sebagai Guru (Forbidden)` | `GET /dashboard/admin` | `{{guru_token}}` | `403` |
| TS-S3-13 | `[Sprint3][Pengajuan] PUT Approve - Guru Pembimbing Optional` | `PUT /pengajuan-pkl/{{pengajuan_id}}/approve` body `{}` | `{{admin_token}}` | `200` (guru_id null) |

---

## 5. Urutan Eksekusi Rekomendasi

1. **01. Authentication** → login admin/guru/siswa (isi token)
2. **11. Pengajuan PKL** (1.1–1.7)
3. **12. Lokasi PKL** (2.1–2.6)
4. **Lanjutan** (3.1–3.3)
5. **99. Test Scenarios** (TS-S3-01 s/d TS-S3-13)

> **Catatan:** Untuk skenario pengajuan, pastikan siswa yang dipakai **sudah konfirmasi profil** dan **belum punya pengajuan pending/lokasi active**. Siapkan siswa kedua untuk skenario duplicate/placed.

---

## 6. Referensi

- API Spec: `.agents/tech-specs/2026/003-api-endpoints.md` (§11, §12, §17.1)
- DB Schema: `.agents/tech-specs/2026/002-database-schema.md` (tabel `pengajuan_pkl`, `lokasi_pkl`, `notifications`)
- Sprint Plan: `.agents/tasks/sprint-3-pkl-plan.md`
- Backend Roadmap: `.agents/tasks/backend-roadmap.md`
- Postman Guide: `.agents/tech-specs/2026/004-postman-collection-guide.md`
- Sprint 2 Playbook: `.agents/tasks/postman-sprint2-execution.md`