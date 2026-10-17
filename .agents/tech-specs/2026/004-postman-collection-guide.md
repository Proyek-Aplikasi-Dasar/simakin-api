# Panduan Postman Collection - SIMAKIN API

Panduan membuat dan mengelola dokumentasi API dalam bentuk Postman Collection untuk proyek SIMAKIN.

---

## 1. Pengenalan

Postman Collection adalah kumpulan request API yang tersimpan dalam format JSON, berisi:
- **Request:** Method, URL, headers, body, authentication
- **Tests:** Assertion otomatis terhadap response
- **Variables:** Nilai dinamis (token, base_url, dll)
- **Documentation:** Deskripsi per endpoint
- **Folder:** Organisir per modul/resource

**Mengapa penting:**
- Frontend (Vue.js & Flutter) bisa langsung test API tanpa baca dokumentasi markdown
- Konsistensi testing antar endpoint
- Bisa di-run otomatis (Collection Runner) sebagai smoke test
- Berguna untuk demo & presentasi

**File output:** `docs/postman/SIMAKIN-API-Collection.json`

---

## 2. Setup Environment

### 2.1 Membuat Environment

Di Postman: **Environments** → **Create Environment** → beri nama `SIMAKIN Local`

Tambahkan variables berikut:

| Variable | Initial Value | Current Value | Keterangan |
|---|---|---|---|
| `base_url` | `http://localhost:8000/api` | `http://localhost:8000/api` | API base URL |
| `web_url` | `http://localhost:8000` | `http://localhost:8000` | Web base (untuk CSRF) |
| `token` | | | Akan diisi otomatis setelah login |
| `admin_email` | `admin@example.com` | `admin@example.com` | Akun admin demo |
| `admin_password` | `admin123` | `admin123` | Password admin |
| `guru_email` | `guru@example.com` | `guru@example.com` | Akun guru demo |
| `guru_password` | `guru123` | `guru123` | Password guru |
| `siswa_email` | `siswa@example.com` | `siswa@example.com` | Akun siswa demo |
| `siswa_password` | `siswa123` | `siswa123` | Password siswa |
| `idempotency_key` | | | Akan diisi otomatis |

### 2.2 Membuat Environment Production (nanti)

Sama seperti di atas, tapi:
- `base_url` = `https://api.simakin.smkn1magelang.sch.id/api`
- `web_url` = `https://api.simakin.smkn1magelang.sch.id`
- Credentials berbeda

---

## 3. Struktur Collection

Buat collection baru bernama **`SIMAKIN API`** dengan struktur folder:

```
SIMAKIN API
├── 01. Authentication
│   ├── POST Login (Web)
│   ├── POST Login (Mobile)
│   ├── GET Get User
│   ├── POST Logout
│   ├── POST Register
│   └── PUT Change Password
├── 02. User Management
│   ├── GET List Users
│   ├── GET Get User Detail
│   ├── PUT Update User
│   ├── PUT Reset Password User
│   └── DELETE Delete User
├── 03. Master - Jurusan
│   ├── GET List Jurusan
│   ├── GET Get Jurusan Detail
│   ├── POST Create Jurusan
│   ├── PUT Update Jurusan
│   ├── DELETE Delete Jurusan
│   └── GET List Kompetensi per Jurusan
├── 04. Master - Kompetensi
│   ├── GET List Kompetensi
│   ├── GET Get Kompetensi Detail
│   ├── POST Create Kompetensi
│   ├── PUT Update Kompetensi
│   ├── DELETE Delete Kompetensi
│   └── POST Bulk Upload Kompetensi
├── 05. Master - Gelombang
│   ├── GET List Gelombang
│   ├── POST Create Gelombang
│   ├── PUT Update Gelombang
│   └── DELETE Delete Gelombang
├── 06. Master - Perusahaan
│   ├── GET List Perusahaan
│   ├── GET Get Perusahaan Detail
│   ├── POST Create Perusahaan
│   ├── PUT Update Perusahaan
│   ├── DELETE Delete Perusahaan
│   ├── POST Bulk Upload Perusahaan
│   ├── POST Attach Jurusan
│   └── DELETE Detach Jurusan
├── 07. Master - Sikap
│   ├── GET List Sikap
│   ├── POST Create Sikap
│   ├── PUT Update Sikap
│   └── DELETE Delete Sikap
├── 08. Master - Waktu Absen
│   ├── GET List Waktu Absen
│   ├── POST Create Waktu Absen
│   ├── PUT Update Waktu Absen
│   └── DELETE Delete Waktu Absen
├── 09. Data - Guru
│   ├── GET List Guru
│   ├── GET Get Guru Detail
│   ├── POST Create Guru
│   ├── PUT Update Guru
│   ├── DELETE Delete Guru
│   ├── POST Bulk Upload Guru
│   └── GET Siswa Bimbingan
├── 10. Data - Siswa
│   ├── GET List Siswa
│   ├── GET Get Siswa Detail
│   ├── POST Create Siswa
│   ├── PUT Update Siswa
│   ├── DELETE Delete Siswa
│   ├── POST Bulk Upload Siswa
│   ├── GET Saya (Me)
│   └── GET Lokasi PKL Siswa
├── 11. Pengajuan PKL
│   ├── GET List Pengajuan
│   ├── GET Get Pengajuan Detail
│   ├── POST Ajukan PKL
│   ├── PUT Approve Pengajuan
│   ├── PUT Reject Pengajuan
│   ├── DELETE Cancel Pengajuan
│   └── PUT Bulk Approve
├── 12. Lokasi PKL
│   ├── GET List Lokasi PKL
│   ├── GET Get Lokasi PKL Detail
│   ├── POST Create Lokasi PKL
│   ├── PUT Update Lokasi PKL
│   ├── PUT Update Status
│   └── DELETE Delete Lokasi PKL
├── 13. Presensi
│   ├── GET List Absensi
│   ├── GET Get Absensi Detail
│   ├── POST Check-in (Dengan Foto)
│   ├── POST Check-in (Tanpa Foto - Izin/Sakit/Libur)
│   ├── PUT Edit Absensi
│   ├── DELETE Batalkan Presensi
│   ├── POST Koreksi Admin
│   ├── GET Rekap Presensi
│   ├── GET Rekap Bulanan Siswa
│   └── POST Upload Foto (Retry)
├── 14. Jurnal Harian
│   ├── GET List Jurnal
│   ├── GET Get Jurnal Detail
│   ├── POST Create Jurnal
│   ├── PUT Edit Jurnal
│   ├── DELETE Delete Jurnal
│   ├── PUT Approve Jurnal
│   ├── PUT Revise Jurnal
│   ├── GET Rekap Jurnal
│   ├── GET Riwayat Jurnal
│   └── POST Upload Foto (Retry)
├── 15. Penilaian
│   ├── GET List Aktivasi Penilaian
│   ├── POST Buka Periode Penilaian
│   ├── PUT Tutup Periode Penilaian
│   ├── GET List Penilaian
│   ├── GET Get Penilaian Detail
│   ├── POST Input Nilai Bulanan
│   ├── PUT Edit Nilai
│   ├── POST Verifikasi (Foto)
│   └── GET Nilai Akhir Siswa
├── 16. Notifications
│   ├── GET List Notifikasi
│   ├── GET Unread Count
│   ├── PUT Mark as Read
│   └── PUT Mark All as Read
├── 17. Dashboard
│   ├── GET Admin Dashboard
│   ├── GET Guru Dashboard
│   └── GET Siswa Dashboard
├── 18. Rekap Excel
│   ├── GET Export Presensi
│   ├── GET Export Jurnal
│   └── GET Export Nilai
└── 99. Test Scenarios
    ├── Scenario: Admin Full Flow
    ├── Scenario: Siswa PKL Flow
    └── Scenario: Error Cases
```

---

## 4. Best Practices

### 4.1 Naming Convention

- **Folder:** `{number}. {Nama Module}` (sort otomatis berurutan)
- **Request:** `{Method} {Resource} {Context}`
  - ✅ `POST Create Jurusan`
  - ✅ `GET List Siswa (With Filters)`
  - ✅ `PUT Approve Pengajuan`
  - ❌ `create jurusan` (kurang method prefix)
  - ❌ `test1` (tidak deskriptif)

### 4.2 Authentication Setup

**Untuk request yang butuh auth:**

1. Buka request → tab **Authorization**
2. Type: `Bearer Token`
3. Token: `{{token}}` (pakai variable environment)
4. Atau jika butuh header custom: tab **Headers** → tambah:
   ```
   Accept: application/json
   X-Client: mobile  (hanya untuk endpoint mobile)
   ```

**Untuk login web (tanpa token, pakai cookie):**
1. Pastikan environment `web_url` sudah diset
2. GET `/sanctum/csrf-cookie` dulu (bisa pakai script otomatis, lihat §4.4)

### 4.3 Pre-request Scripts

**Login auto-save token (di request Login Mobile):**

Tambahkan tab **Tests**:
```javascript
pm.test("Login berhasil", function () {
    pm.response.to.have.status(200);
});

const response = pm.response.json();

if (response.token) {
    pm.environment.set("token", response.token);
    console.log("Token disimpan ke environment.");
}

if (response.user) {
    pm.environment.set("user_id", response.user.id);
    pm.environment.set("role_id", response.user.role_id);
}
```

**Set idempotency key otomatis (untuk POST presensi/jurnal):**

Tambahkan tab **Pre-request Script**:
```javascript
pm.environment.set("idempotency_key", pm.variables.replaceIn("{{$guid}}"));
```

Di tab **Headers**:
```
X-Idempotency-Key: {{idempotency_key}}
```

### 4.4 Pre-request: Fetch CSRF Token (Web Login)

Di request `GET /sanctum/csrf-cookie`, tambah **Tests**:
```javascript
pm.test("CSRF cookie fetched", function () {
    pm.response.to.have.status(204);
});
```

Di request `POST Login (Web)`, tambah **Pre-request Script**:
```javascript
// Fetch CSRF cookie dulu
pm.sendRequest({
    url: pm.environment.get("web_url") + "/sanctum/csrf-cookie",
    method: "GET",
    header: { "Accept": "application/json" }
}, function (err, res) {
    // Cookie akan otomatis tersimpan jika pakai cookie jar Postman
});
```

Di tab **Headers** request Login Web:
```
Accept: application/json
Origin: {{web_url}}
```

### 4.5 Tests Scripts (Assertion)

**Template standar untuk setiap request:**

```javascript
// Status code
pm.test("Status code sesuai", function () {
    pm.response.to.have.status(200); // sesuaikan: 200, 201, 422, dll
});

// Response time (performance)
pm.test("Response time < 2000ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(2000);
});

// Content type
pm.test("Content-Type JSON", function () {
    pm.response.to.have.header("Content-Type", /application\/json/);
});

// Structure check (untuk list)
pm.test("Response memiliki data array", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("data");
    pm.expect(json.data).to.be.an("array");
});

// Structure check (untuk single resource)
pm.test("Response memiliki data object", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("data");
    pm.expect(json.data).to.be.an("object");
});
```

**Untuk error test (422):**
```javascript
pm.test("Validasi gagal (422)", function () {
    pm.response.to.have.status(422);
});

pm.test("Response memiliki errors", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("errors");
    pm.expect(json.errors).to.have.property("email");
});
```

### 4.6 Documentation per Request

Di setiap request, isi tab **Documentation** (ikon buku di pojok kanan):

```markdown
## Deskripsi
Input penilaian bulanan siswa oleh guru pembimbing.

## Authorization
- Role: guru

## Request Body
| Field | Type | Required | Description |
|---|---|---|---|
| siswa_id | integer | Ya | ID siswa |
| aktivasi_penilaian_id | integer | Ya | ID periode penilaian |
| kompetensi | array | Ya | Array {kompetensi_id, nilai} |
| sikap | array | Ya | Array {sikap_id, nilai} |

## Business Rules
- Periode harus aktif (`is_active = true`)
- Nilai 0-100

## Error
- 422 `PERIODE_CLOSED`: Periode sudah ditutup
- 422 `VALIDATION_ERROR`: Field wajib kosong

## Related
- Endpoint: `GET /api/penilaian/{id}` untuk melihat hasil
```

### 4.7 Examples (Contoh Response)

Di setiap request, tambahkan **Examples** (tab Examples):

1. Click **Examples** → **Add Example**
2. Beri nama: `Success (200)` atau `Validation Error (422)`
3. Isi response body contoh:

**Success example:**
```json
{
  "data": {
    "id": 1,
    "siswa_id": 1,
    "nilai_akhir": 87.50,
    "kompetensi": [...],
    "sikap": [...]
  }
}
```

**Error example:**
```json
{
  "message": "Periode penilaian sudah ditutup.",
  "code": "PERIODE_CLOSED"
}
```

---

## 5. Test Scenarios (Collection Runner)

### 5.1 Scenario: Admin Full Flow

Urutan request untuk dijalankan via **Collection Runner**:

1. `POST Login (Web)` sebagai admin
2. `POST Create Jurusan`
3. `POST Create Kompetensi`
4. `POST Create Gelombang`
5. `POST Create Perusahaan`
6. `POST Create Guru`
7. `POST Create Siswa`
8. `POST Create Lokasi PKL`
9. `GET Admin Dashboard`
10. `GET List Siswa`

### 5.2 Scenario: Siswa PKL Flow

1. `POST Login (Mobile)` sebagai siswa
2. `GET Saya (Me)`
3. `POST Ajukan PKL` (alternatif: admin assign via Lokasi PKL)
4. `POST Check-in (Dengan Foto)`
5. `POST Create Jurnal`
6. `GET List Jurnal`
7. `GET Notifications`

### 5.3 Scenario: Error Cases

1. `POST Login` dengan password salah → assert 422
2. `GET /api/user` tanpa token → assert 401
3. `POST Create Jurusan` sebagai siswa → assert 403
4. `POST Check-in` dua kali → assert 422 `ABSENSI_DUPLICATE`
5. `POST Create Jurnal` tanpa presensi → assert 422 `ABSENSI_REQUIRED`

### 5.4 Menjalankan Collection Runner

1. Buka collection → **Run** (ikon play)
2. Pilih environment: `SIMAKIN Local`
3. Set **Iteration**: 1
4. Set **Delay**: 100ms (untuk hindari rate limit)
5. **Run SIMAKIN API**
6. Lihat hasil: semua test pass/fail

---

## 6. Export & Share

### 6.1 Export Collection

1. Klik collection → **⋯** → **Export**
2. Pilih format: **Collection v2.1**
3. Simpan di: `docs/postman/SIMAKIN-API-Collection.json`

### 6.2 Import di Komputer Lain

1. **Import** → pilih file JSON
2. Pilih environment yang sesuai
3. Login ulang untuk dapat token baru

### 6.3 Commit ke Git

```bash
git add docs/postman/SIMAKIN-API-Collection.json
git commit -m "[BE] docs: update postman collection"
```

### 6.4 Sync dengan Dokumentasi Markdown

Setiap kali menambah/mengubah endpoint:
1. Update `docs/API-Docs-fondasi.md` (atau buat file baru per modul)
2. Update `.agents/tech-specs/2026/003-api-endpoints.md`
3. Update Postman collection
4. Commit ketiganya bersamaan

---

## 7. Bonus: Auto-generate dari Laravel

### 7.1 Pakai Scribe (Rekomendasi)

Install:
```bash
composer require --dev knuckleswtf/scribe
php artisan install:scribe
```

Tambahkan docblocks di controller:
```php
/**
 * Input penilaian bulanan siswa.
 *
 * @unauthenticated
 * @group Penilaian
 *
 * @bodyParam siswa_id integer required ID siswa. Example: 1
 * @bodyParam nilai decimal required Nilai 0-100. Example: 85
 */
public function store(StorePenilaianRequest $request)
```

Generate:
```bash
php artisan scribe:generate
```

Output: HTML docs + Postman collection + OpenAPI spec di `resources/docs/`.

### 7.2 Kapan Pakai Scribe?

- Jika dokumentasi markdown + Postman manual terlalu ribet
- Jika butuh auto-sync setiap kali ubah controller
- Untuk PAD 2 (jika waktu ada)

**Untuk PAD 1:** Manual cukup (lebih kontrol, tidak perlu setup tambahan).

---

## 8. Checklist

### Setup Awal (sekali saja)
- [ ] Install Postman (desktop app)
- [ ] Create environment `SIMAKIN Local`
- [ ] Isi semua variables (base_url, credentials)
- [ ] Create collection `SIMAKIN API`
- [ ] Buat struktur folder (§3)

### Per Endpoint Baru
- [ ] Buat request dengan naming convention (§4.1)
- [ ] Set authorization (§4.2)
- [ ] Tambah pre-request script jika perlu (§4.3-4.4)
- [ ] Tambah tests assertion (§4.5)
- [ ] Isi dokumentasi deskripsi (§4.6)
- [ ] Tambah examples (success + error) (§4.7)
- [ ] Test run manual
- [ ] Export & commit ke git (§6)

### Sebelum Demo / Sprint Review
- [ ] Jalankan Collection Runner full (§5.4)
- [ ] Pastikan semua test pass
- [ ] Update dokumentasi markdown
- [ ] Export collection terbaru

---

## 9. Troubleshooting

| Masalah | Solusi |
|---|---|
| 401 Unauthenticated | Token belum ada → jalankan Login dulu, pastikan `{{token}}` terisi |
| 419 Page Expired | CSRF token expired → jalankan `GET /sanctum/csrf-cookie` dulu |
| 403 Forbidden | Role salah → login dengan akun yang sesuai (admin/guru/siswa) |
| 422 tapi tidak ada `errors` | Response custom business error → cek `code` field |
| CORS error | Pastikan `Origin` header sesuai `FRONTEND_URL` di `.env` |
| Rate limit 429 | Tambah delay antar request di Collection Runner |
| Cookie tidak tersimpan | Postman → Settings → General → cek `Automatically follow redirects` |
| Token expired | Login ulang (token Sanctum bisa expire sesuai config) |
