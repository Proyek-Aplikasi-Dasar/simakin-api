# Playbook Eksekusi Postman — Sprint 4 (Presensi Harian)

Dokumen kerja untuk **menambahkan folder 13 (Presensi) ke collection `SIMAKIN API`** (GUI Postman) bagi endpoint Sprint 4. Isi siap-tempel: headers, body, *Pre-request Script*, *Tests*, tab *Documentation*, dan *Examples*.

**Panduan konsep/umum:** `.agents/tech-specs/2026/004-postman-collection-guide.md`  
**Pola autentikasi (folder 01):** `.agents/tasks/postman-auth-execution.md`  
**Playbook Sprint 3:** `.agents/tasks/postman-sprint3-execution.md`

**Ruang lingkup:** Folder `13. Presensi` (9 request) + integrasi `siswa-bimbingan` & `dashboard/admin` (sudah ada) + skenario di folder `99. Test Scenarios`.

| Folder | Requests | Keterangan |
|---|---|---|
| 13. Presensi | 9 | List, Rekap, Periode, Upload Foto, Detail, Check-in, Edit, Delete, Koreksi |

> **Keputusan auth:** Siswa memakai `{{siswa_token}}` untuk check-in/edit/delete/upload-foto. Admin/guru memakai `{{admin_token}}`/`{{guru_token}}` untuk list/rekap/periode/koreksi. Check-in memerlukan profil siswa sudah dikonfirmasi (`profil.confirmed`) dan lokasi PKL aktif.

---

## 0. Prasyarat

1. Folder `01. Authentication` selesai (token admin/guru/siswa terisi).
2. Backend jalan + DB ter-seed (Sprint 4 seeder: sample presensi).
3. Environment `SIMAKIN Local` aktif dengan variabel: `admin_token`, `guru_token`, `siswa_token`, `siswa_id`, `absensi_id`.
4. Ada siswa **sudah konfirmasi profil** dan punya **lokasi PKL active** (untuk check-in).
5. `php artisan storage:link` sudah dijalankan (untuk URL foto).

### 0.1 Standar headers

**Admin/Guru:**
```
Accept: application/json
Authorization: Bearer {{admin_token}}   (atau {{guru_token}})
```

**Siswa (JSON):**
```
Accept: application/json
Authorization: Bearer {{siswa_token}}
```

**Siswa (Multipart):**
```
Accept: application/json
Authorization: Bearer {{siswa_token}}
Content-Type: multipart/form-data
```

---

## 1. Folder `13. Presensi`

Buat request **sesuai urutan di bawah**.

### 1.1 `GET List Absensi`

- **Method/URL:** `GET {{base_url}}/absensi`
- **Headers:** standar admin/guru (atau siswa)
- **Query Params (opsional):** `siswa_id`, `tanggal`, `tanggal_mulai`, `tanggal_selesai`, `status`, `page`
- **Tests:** `T-List`
- **Documentation:** List presensi (admin/guru semua; siswa hanya miliknya).

### 1.2 `GET Rekap Absensi`

- **Method/URL:** `GET {{base_url}}/absensi/rekap`
- **Headers:** standar admin/guru
- **Query Params (opsional):** `gelombang_id`, `jurusan_id`, `tanggal_mulai`, `tanggal_selesai`
- **Tests:** `T-List`-like
- **Documentation:** Rekap agregat per siswa (`hadir`, `izin`, `sakit`, `libur`, `tidak_absen`).

### 1.3 `GET Rekap Periode Siswa`

- **Method/URL:** `GET {{base_url}}/absensi/siswa/{{siswa_id}}/periode`
- **Headers:** standar (siswa miliknya pakai `{{siswa_token}}`)
- **Query Params (opsional):** `bulan`, `tahun`
- **Tests:** `T-Detail`-like
- **Documentation:** Rekap bulanan 1 siswa + rincian harian.

### 1.4 `POST Upload Foto (Retry)`

- **Method/URL:** `POST {{base_url}}/absensi/upload-foto`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Body (form-data):**
  | Key | Type | Value | Keterangan |
  |---|---|---|---|
  | `absensi_id` | Text | `{{absensi_id}}` | ID absensi partial (failed) |
  | `foto_masuk` | File | pilih file gambar | jpeg/jpg/png, max 5MB |
- **Tests:** `T-OK`
- **Documentation:** Retry upload foto; set `status_submit = success`.

### 1.5 `GET Get Absensi Detail`

- **Method/URL:** `GET {{base_url}}/absensi/{{absensi_id}}`
- **Headers:** standar
- **Tests:** `T-Detail`

### 1.6 `POST Check-in (Siswa)`

- **Method/URL:** `POST {{base_url}}/absensi`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}`
- **Body (form-data):**
  | Key | Type | Value | Keterangan |
  |---|---|---|---|
  | `status` | Text | `hadir` | hadir/izin/sakit/libur |
  | `latitude` | Text | `-7.40123456` | GPS latitude |
  | `longitude` | Text | `110.40123456` | GPS longitude |
  | `foto_masuk` | File | pilih file gambar | Wajib jika status=hadir (kecuali partial `failed`) |
  | `keterangan` | Text | _(opsional)_ | Wajib jika status != hadir |
  | `tanggal` | Text | _(opsional)_ | Default hari ini (YYYY-MM-DD) |
  | `status_submit` | Text | `success` | `success` (default) / `failed` (partial save) |
- **Tests:**
  ```javascript
  pm.test("Status 201 Created", function () {
      pm.response.to.have.status(201);
  });
  const json = pm.response.json();
  pm.test("Response berisi success & data", function () {
      pm.expect(json).to.have.property("success");
      pm.expect(json).to.have.property("data");
      pm.expect(json.data).to.have.property("id");
  });
  if (json.data && json.data.id) {
      pm.environment.set("absensi_id", json.data.id);
  }
  ```
- **Documentation:** Check-in presensi. Wajib profil confirmed & lokasi PKL active. Response menyertakan `next_action: /jurnal/create` jika status `hadir`.

### 1.7 `PUT Edit Absensi (Siswa)`

- **Method/URL:** `PUT {{base_url}}/absensi/{{absensi_id}}`
- **Headers:** `Accept: application/json` | `Authorization: Bearer {{siswa_token}}` | `Content-Type: application/json`
- **Body (raw → JSON):**
  ```json
  {
    "status": "izin",
    "keterangan": "Izin mendadak",
    "status_submit": "success"
  }
  ```
- **Tests:** `T-OK`
- **Documentation:** Edit hanya jika `status_submit = failed` → `ABSENSI_EDIT_BLOCKED` jika sudah success.

### 1.8 `DELETE Batalkan Presensi`

- **Method/URL:** `DELETE {{base_url}}/absensi/{{absensi_id}}`
- **Headers:** standar (admin/siswa)
- **Tests:** `T-Delete`
- **Documentation:** Batalkan presensi (admin atau siswa pemilik). Hapus file foto jika ada.

### 1.9 `POST Koreksi Presensi (Admin/Guru)`

- **Method/URL:** `POST {{base_url}}/absensi/{{absensi_id}}/koreksi`
- **Headers:** standar admin/guru
- **Body (raw → JSON):**
  ```json
  {
    "status": "sakit",
    "keterangan": "Dikoreksi oleh admin",
    "jam_masuk": "08:30"
  }
  ```
- **Tests:** `T-OK`
- **Documentation:** Koreksi manual (admin/guru; guru hanya siswa bimbingannya). Set `dikoreksi_oleh`, kirim notifikasi `absensi-corrected`.

---

## 2. Integrasi (sudah ada di collection)

- `09. Data - Guru` → `GET Siswa Bimbingan`: kini mengisi `presensi_hari_ini` (`status`, `jam_masuk`) berdasarkan `?tanggal=` (default hari ini).
- `13. Dashboard` → `GET Dashboard Admin`: kini mengisi `presensi_hari_ini` (`hadir`, `izin`, `sakit`, `tidak_absen`) nyata.

---

## 3. Tambahan Folder `99. Test Scenarios`

Tambahkan skenario Sprint 4 berikut (prefix `[Sprint4]`):

| # | Request Name | Method/URL | Auth | Expected |
|---|---|---|---|---|
| TS-S4-01 | `[Sprint4][Absensi] POST Check-in - Tanpa Token` | `POST /absensi` | tanpa auth | `401` |
| TS-S4-02 | `[Sprint4][Absensi] POST Check-in - Belum Konfirmasi (Gate)` | `POST /absensi` | siswa belum konfirmasi | `403 PROFIL_BELUM_KONFIRMASI` |
| TS-S4-03 | `[Sprint4][Absensi] POST Check-in - Tanpa Lokasi PKL` | `POST /absensi` (siswa tanpa lokasi active) | `{{siswa_token}}` | `422 GELOMBANG_NOT_ACTIVE` |
| TS-S4-04 | `[Sprint4][Absensi] POST Check-in - Duplicate` | `POST /absensi` (2x) | `{{siswa_token}}` | `422 ABSENSI_DUPLICATE` |
| TS-S4-05 | `[Sprint4][Absensi] POST Check-in - Hadir Tanpa Foto` | `POST /absensi` body `{ "status": "hadir" }` | `{{siswa_token}}` | `422` |
| TS-S4-06 | `[Sprint4][Absensi] POST Check-in - Izin Tanpa Keterangan` | `POST /absensi` body `{ "status": "izin" }` | `{{siswa_token}}` | `422` |
| TS-S4-07 | `[Sprint4][Absensi] PUT Edit - Sudah Success (Blocked)` | `PUT /absensi/{{absensi_id}}` | `{{siswa_token}}` | `422 ABSENSI_EDIT_BLOCKED` |
| TS-S4-08 | `[Sprint4][Absensi] POST Koreksi - Sebagai Siswa (Forbidden)` | `POST /absensi/{{absensi_id}}/koreksi` | `{{siswa_token}}` | `403` |
| TS-S4-09 | `[Sprint4][Absensi] POST Koreksi - Guru Bukan Pembimbing` | `POST /absensi/{{absensi_id}}/koreksi` | `{{guru_token}}` (bukan pembimbing) | `403` |
| TS-S4-10 | `[Sprint4][Absensi] DELETE - Bukan Pemilik` | `DELETE /absensi/{{absensi_id}}` | siswa lain token | `403` |
| TS-S4-11 | `[Sprint4][Absensi] POST Upload Foto - absensi_id Invalid` | `POST /absensi/upload-foto` body `{ "absensi_id": 99999 }` | `{{siswa_token}}` | `422` |
| TS-S4-12 | `[Sprint4][Absensi] GET Rekap - Sebagai Siswa (Forbidden)` | `GET /absensi/rekap` | `{{siswa_token}}` | `403` |
| TS-S4-13 | `[Sprint4][Absensi] GET Periode - Siswa Lain (Forbidden)` | `GET /absensi/siswa/{{siswa_lain_id}}/periode` | `{{siswa_token}}` | `403` |

---

## 4. Urutan Eksekusi Rekomendasi

1. **01. Authentication** → login admin/guru/siswa (isi token)
2. **13. Presensi** (1.1–1.9)
3. **Integrasi** (siswa-bimbingan, dashboard admin)
4. **99. Test Scenarios** (TS-S4-01 s/d TS-S4-13)

> **Catatan:** Untuk check-in, pastikan siswa yang dipakai **sudah konfirmasi profil** dan punya **lokasi PKL active**. Siapkan siswa kedua (belum konfirmasi / tanpa lokasi) untuk skenario gate.

---

## 5. Referensi

- API Spec: `.agents/tech-specs/2026/003-api-endpoints.md` (§13)
- DB Schema: `.agents/tech-specs/2026/002-database-schema.md` (tabel `absensi_siswa`)
- Sprint Plan: `.agents/tasks/sprint-4-presensi-plan.md`
- Backend Roadmap: `.agents/tasks/backend-roadmap.md`
- Postman Guide: `.agents/tech-specs/2026/004-postman-collection-guide.md`
- Sprint 3 Playbook: `.agents/tasks/postman-sprint3-execution.md`