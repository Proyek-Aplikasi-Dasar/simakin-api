# Action Plan: Tindak Lanjut Catatan UI/UX & Front-End

**Branch:** `rev/catatan-uiux-fe-1`  
**Created:** 5 November 2026  
**Sprint Target:** Sprint 5 (Jurnal Harian) & Sprint 6 (Penilaian Bulanan)

---

## Phase 1: Update Dokumentasi (Hari 1-2)

### 1.1 Update Backend Roadmap

**File:** `.agents/tasks/backend-roadmap.md`

**Perubahan Sprint 5:**
```markdown
## Sprint 5: Jurnal Harian (11-17 Nov)

**Goal:** Siswa input jurnal, **TIDAK ADA approval guru**, admin bisa edit semua jurnal.

### Task 5.1: Tabel & Endpoint Jurnal
- [ ] Migration: `create_jurnal_harian_table` (UNIQUE siswa_id+tanggal)
- [ ] Model: `JurnalHarian` (relasi: Siswa, Kompetensi, User (edited_by))
- [ ] Seeder: sample jurnal
- [ ] Controller: `JurnalHarianController`
- [ ] Endpoint: GET list (filter), GET/{id}
- [ ] File upload: foto dokumentasi

**Estimasi:** 1 hari

### Task 5.2: CRUD Jurnal (Siswa & Admin)
- [ ] `POST /api/jurnal` - create
- [ ] Business: validasi presensi `hadir` hari ini (`ABSENSI_REQUIRED`)
- [ ] `PUT /api/jurnal/{id}` - Siswa edit milik sendiri, Admin edit semua
- [ ] `DELETE /api/jurnal/{id}` - Siswa delete milik sendiri, Admin delete semua
- [ ] `POST /api/jurnal/upload-foto` (retry foto)
- [ ] Test: create + edit + delete + validation

**Estimasi:** 1.5 hari

### Task 5.3: Riwayat & Rekap (TANPA status review)
- [ ] `GET /api/jurnal/riwayat` - kronologis per tanggal (asc/desc)
- [ ] `GET /api/jurnal/rekap` - total jurnal per siswa (tanpa breakdown pending/approved)
- [ ] Test

**Estimasi:** 1 hari

### Task 5.4: API Docs + Postman
- [ ] Update dokumentasi Sprint 5

**Estimasi:** 0.5 hari

**Sprint 5 Total: 4 hari** (buffer 1 hari)
```

**Perubahan Sprint 6:**
```markdown
## Sprint 6: Penilaian Bulanan (18-24 Nov)

**Goal:** Input nilai kompetensi + sikap, verifikasi foto dengan **token 6 digit** + **metadata GPS lengkap**.

### Task 6.1: Tabel Penilaian (dengan field tambahan)
- [ ] Migration: `create_aktivasi_penilaian_table`
- [ ] Migration: `create_penilaian_bulanan_table` 
  - Field tambahan: `verifikasi_waktu`, `jarak_dari_pkl`, `dalam_radius`
  - Field token: `token_verifikasi`, `token_generated_at`, `token_used_at`
- [ ] Migration: `create_penilaian_kompetensi_table`
- [ ] Migration: `create_penilaian_sikap_table`
- [ ] Migration: alter `perusahaan` tambah `radius_presensi`, `pembimbing_industri`, `bidang_usaha`
- [ ] Models + relasi (HasMany detail)
- [ ] Seeder: sample data penilaian

**Estimasi:** 1.5 hari

### Task 6.4: Verifikasi Foto (Siswa) dengan Token + GPS
- [ ] `POST /api/penilaian/{id}/generate-token` - generate token 6 digit (guru/admin)
- [ ] `POST /api/penilaian/{id}/verifikasi` - upload foto + validasi token + GPS metadata
- [ ] Logic: 
  - Validasi token (belum expired, belum dipakai)
  - Hitung jarak dari koordinat perusahaan (Haversine formula)
  - Set flag `dalam_radius` berdasarkan `perusahaan.radius_presensi`
  - Simpan `verifikasi_waktu`, `jarak_dari_pkl`, `dalam_radius`
  - Mark token as used
- [ ] File upload validation
- [ ] Test

**Estimasi:** 1.5 hari
```

---

### 1.2 Update Database Schema

**File:** `.agents/tech-specs/2026/002-database-schema.md`

**Tambah setelah tabel perusahaan (sekitar line 200):**
```markdown
## 7. Tabel `perusahaan` (UPDATE)

**Field Tambahan untuk Sprint 6:**

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| radius_presensi | int unsigned | YES | 100 | - | Radius presensi dalam meter (default 100m) |
| pembimbing_industri | varchar(255) | YES | NULL | - | Nama pembimbing dari pihak industri |
| bidang_usaha | varchar(100) | YES | NULL | - | Sektor bisnis perusahaan |

**Business Rules UPDATE:**
- `radius_presensi` digunakan untuk validasi flag `dalam_radius` saat verifikasi penilaian
- Default 100 meter jika tidak diisi
```

**Tambah tabel jurnal_harian (sekitar line 416):**
```markdown
## 15. Tabel `jurnal_harian`

**Deskripsi:** Jurnal harian siswa PKL. **TIDAK ADA approval**, siswa dan admin bisa edit.

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | auto | PK | Primary key |
| siswa_id | bigint unsigned | NO | - | FK, INDEX | → siswa.id |
| tanggal | date | NO | - | INDEX | Tanggal jurnal |
| kompetensi_id | bigint unsigned | NO | - | FK | → kompetensi.id |
| deskripsi | text | NO | - | - | Deskripsi kegiatan (unlimited) |
| foto | varchar(255) | YES | NULL | - | Path foto dokumentasi |
| edited_by | bigint unsigned | YES | NULL | FK | → users.id (siapa yang terakhir edit) |
| edited_at | timestamp | YES | NULL | - | Kapan terakhir diedit |
| created_at | timestamp | YES | NULL | - | - |
| updated_at | timestamp | YES | NULL | - | - |

**Foreign Keys:**
- `siswa_id` → `siswa(id)` ON DELETE CASCADE
- `kompetensi_id` → `kompetensi(id)`
- `edited_by` → `users(id)` ON DELETE SET NULL

**Indexes:**
- UNIQUE(`siswa_id`, `tanggal`)
- INDEX(`tanggal`)

**Business Rules:**
- Siswa hanya bisa create jurnal jika sudah presensi `hadir` hari itu
- Siswa bisa edit/delete jurnal milik sendiri kapan saja
- Admin bisa edit/delete semua jurnal
- Guru bisa view jurnal siswa bimbingan (read-only)
- Satu siswa hanya bisa 1 jurnal per hari (UNIQUE constraint)
```

**Update tabel penilaian_bulanan (sekitar line 490):**
```markdown
## 17. Tabel `penilaian_bulanan` (UPDATE)

**Field Tambahan untuk Metadata GPS:**

| Column | Type | Nullable | Default | Description |
|---|---|---|---|---|
| verifikasi_waktu | timestamp | YES | NULL | Waktu saat siswa upload foto (dari EXIF atau server time) |
| jarak_dari_pkl | decimal(8,2) | YES | NULL | Jarak dalam meter dari koordinat perusahaan (Haversine) |
| dalam_radius | boolean | YES | NULL | Flag apakah verifikasi dalam radius perusahaan |

**Field Tambahan untuk Token Verifikasi:**

| Column | Type | Nullable | Default | Index | Description |
|---|---|---|---|---|---|
| token_verifikasi | varchar(6) | YES | NULL | UNIQUE | Token 6 digit untuk verifikasi siswa |
| token_generated_at | timestamp | YES | NULL | - | Kapan token di-generate |
| token_used_at | timestamp | YES | NULL | - | Kapan token digunakan |

**Business Rules UPDATE:**
- Token expire 24 jam setelah `token_generated_at`
- Token hanya bisa dipakai sekali (`token_used_at` terisi)
- Siswa input token + upload foto verifikasi
- Sistem hitung `jarak_dari_pkl` otomatis (server-side)
- Sistem set `dalam_radius` otomatis (compare jarak dengan `perusahaan.radius_presensi`)
```

---

### 1.3 Update API Endpoints

**File:** `.agents/tech-specs/2026/003-api-endpoints.md`

**Update Section 14 (Jurnal Harian) - line 576:**
```markdown
## 14. Jurnal Harian (8 endpoints) 📋

**PERUBAHAN PENTING:** Tidak ada approval/review oleh guru. Siswa bisa edit kapan saja, admin bisa edit semua.

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| GET | /api/jurnal | Required | admin, guru, siswa | List jurnal |
| GET | /api/jurnal/{id} | Required | admin, guru, siswa | Detail jurnal |
| POST | /api/jurnal | Required | siswa | Create jurnal |
| PUT | /api/jurnal/{id} | Required | siswa, admin | Edit jurnal |
| DELETE | /api/jurnal/{id} | Required | siswa, admin | Delete jurnal |
| GET | /api/jurnal/rekap | Required | admin, guru | Rekap jurnal |
| GET | /api/jurnal/riwayat | Required | admin, guru, siswa | Riwayat kronologis |
| POST | /api/jurnal/upload-foto | Required | siswa | Upload foto terpisah (retry) |

**Authorization:**
- Siswa: CRUD hanya untuk jurnal milik sendiri
- Admin: CRUD semua jurnal
- Guru: Read-only jurnal siswa bimbingan

**GET /api/jurnal/rekap** - Query: `gelombang_id`, `siswa_id`, `tanggal_mulai`, `tanggal_selesai`
Response: total jurnal per siswa (tanpa breakdown status review).
```

**Update Section 15 (Penilaian) - line 620, tambah endpoint:**
```markdown
## 15. Penilaian Bulanan (12 endpoints) 📋

### Penilaian (Guru/Industri via akun siswa)

| Method | Endpoint | Auth | Role | Description |
|---|---|---|---|---|
| ... (endpoint existing) ... |
| POST | /api/penilaian/{id}/generate-token | Required | guru, admin | Generate token 6 digit |
| ... (endpoint existing) ... |

**POST /api/penilaian/{id}/generate-token** (guru/admin):
Response:
```json
{
  "penilaian_id": 1,
  "token": "482916",
  "expires_at": "2026-11-06T10:00:00Z"
}
```

Logic: Generate random 6 digit, simpan ke `token_verifikasi`, set `token_generated_at`.

**POST /{id}/verifikasi** (siswa) - Multipart UPDATE:
```
token: "482916"  (required)
foto_verifikasi: (file - required)
latitude: -7.xxx (required)
longitude: 110.xxx (required)
```

Logic UPDATE:
1. Validasi token (exists, not expired, not used)
2. Upload foto
3. Hitung `jarak_dari_pkl` (Haversine dari lat/long siswa ke `perusahaan` via `lokasi_pkl`)
4. Set `dalam_radius` = (jarak <= `perusahaan.radius_presensi`)
5. Simpan `verifikasi_waktu` (dari EXIF atau now())
6. Mark `token_used_at` = now()
7. Set `verified_at` = now()

Response:
```json
{
  "id": 1,
  "verified_at": "2026-11-05T10:30:00Z",
  "jarak_dari_pkl": 45.8,
  "dalam_radius": true,
  "pesan": "Verifikasi berhasil. Anda berada dalam radius lokasi PKL."
}
```

Error: 
- 422 `TOKEN_INVALID` (token salah/tidak ditemukan)
- 422 `TOKEN_EXPIRED` (token sudah expire >24 jam)
- 422 `TOKEN_USED` (token sudah dipakai)
```

---

### 1.4 Update PRD

**File:** `.agents/PRD.md`

**Update line 54-64 (Scope PAD 1):**
```markdown
3. **Presensi Harian** - Swafoto + koordinat GPS, status jelas, edit/batal jika salah
4. **Jurnal Harian** - Terintegrasi kompetensi kurikulum, **TANPA approval guru**, admin bisa edit
5. **Penilaian Bulanan** - Input nilai kompetensi + sikap, verifikasi dengan **token 6 digit + metadata GPS lengkap** (waktu, koordinat, jarak, flag radius)
```

**Update line 88-99 (Pain Points & Solutions):**
```markdown
| 11 | Kecurangan penilaian bulanan | Token 6 digit + watermark GPS + validasi jarak/radius + notifikasi ke guru |
```

---

## Phase 2: Database Migration (Hari 3)

### 2.1 Alter Table Perusahaan

```bash
php artisan make:migration add_fields_to_perusahaan_table --table=perusahaan
```

**Migration content:**
```php
public function up()
{
    Schema::table('perusahaan', function (Blueprint \) {
        \->unsignedInteger('radius_presensi')->default(100)->after('longitude');
        \->string('pembimbing_industri')->nullable()->after('radius_presensi');
        \->string('bidang_usaha', 100)->nullable()->after('pembimbing_industri');
    });
}

public function down()
{
    Schema::table('perusahaan', function (Blueprint \) {
        \->dropColumn(['radius_presensi', 'pembimbing_industri', 'bidang_usaha']);
    });
}
```

### 2.2 Create Table Jurnal Harian

```bash
php artisan make:migration create_jurnal_harian_table
```

**Migration content:**
```php
public function up()
{
    Schema::create('jurnal_harian', function (Blueprint \) {
        \->id();
        \->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
        \->date('tanggal');
        \->foreignId('kompetensi_id')->constrained('kompetensi');
        \->text('deskripsi');
        \->string('foto')->nullable();
        \->foreignId('edited_by')->nullable()->constrained('users')->onDelete('set null');
        \->timestamp('edited_at')->nullable();
        \->timestamps();
        
        \->unique(['siswa_id', 'tanggal']);
        \->index('tanggal');
    });
}
```

### 2.3 Update Seeder Perusahaan

**File:** `database/seeders/PerusahaanSeeder.php`

Tambah field baru di seeder:
```php
'radius_presensi' => 100,
'pembimbing_industri' => 'Budi Santoso, S.T.',
'bidang_usaha' => 'Teknologi Informasi',
```

---

## Phase 3: Model & Seeder Jurnal (Hari 4)

### 3.1 Model JurnalHarian

```bash
php artisan make:model JurnalHarian
```

**Model content:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalHarian extends Model
{
    protected \ = 'jurnal_harian';
    
    protected \ = [
        'siswa_id',
        'tanggal',
        'kompetensi_id',
        'deskripsi',
        'foto',
        'edited_by',
        'edited_at',
    ];
    
    protected \ = [
        'tanggal' => 'date',
        'edited_at' => 'datetime',
    ];
    
    public function siswa(): BelongsTo
    {
        return \->belongsTo(Siswa::class);
    }
    
    public function kompetensi(): BelongsTo
    {
        return \->belongsTo(Kompetensi::class);
    }
    
    public function editor(): BelongsTo
    {
        return \->belongsTo(User::class, 'edited_by');
    }
}
```

### 3.2 Seeder Jurnal

```bash
php artisan make:seeder JurnalHarianSeeder
```

---

## Phase 4: Testing & Commit (Hari 4)

### 4.1 Run Migration

```bash
php artisan migrate
```

### 4.2 Run Seeder

```bash
php artisan db:seed --class=PerusahaanSeeder
php artisan db:seed --class=JurnalHarianSeeder
```

### 4.3 Git Commit

```bash
git add .
git commit -m "[REV] Update dokumentasi & database schema - Tindak lanjut catatan UI/UX & FE

- Update backend roadmap: hapus approval jurnal Sprint 5
- Update database schema: jurnal_harian (tanpa approval), perusahaan (tambah 3 field), penilaian_bulanan (tambah metadata GPS + token)
- Update API endpoints: jurnal (hapus approve/revise), penilaian (tambah generate-token)
- Update PRD: klarifikasi workflow jurnal & penilaian
- Migration: alter perusahaan, create jurnal_harian
- Model: JurnalHarian
- Seeder: update perusahaan, tambah jurnal_harian"
```

---

## Phase 5: Sprint 5 Implementation (Hari 5-8)

Lihat `.agents/tasks/backend-roadmap.md` Sprint 5 yang sudah direvisi.

---

## Phase 6: Sprint 6 Preparation (Hari 9)

### 6.1 Create Migrations Penilaian (4 tabel)

```bash
php artisan make:migration create_aktivasi_penilaian_table
php artisan make:migration create_penilaian_bulanan_table
php artisan make:migration create_penilaian_kompetensi_table
php artisan make:migration create_penilaian_sikap_table
```

### 6.2 Update penilaian_bulanan Migration

Tambah field:
- `verifikasi_waktu`
- `jarak_dari_pkl`
- `dalam_radius`
- `token_verifikasi` (UNIQUE)
- `token_generated_at`
- `token_used_at`

---

## Checklist Progress

### Documentation
- [ ] Update `backend-roadmap.md` Sprint 5 & 6
- [ ] Update `002-database-schema.md` (perusahaan, jurnal_harian, penilaian_bulanan)
- [ ] Update `003-api-endpoints.md` (jurnal, penilaian)
- [ ] Update `PRD.md` (scope & pain points)
- [ ] Create `Analisis_Catatan_UIUX_FE_Sprint_4-5.md` ✅
- [ ] Create `Action_Plan_Catatan_UIUX_FE.md` ✅

### Database
- [ ] Migration: alter `perusahaan` (3 fields)
- [ ] Migration: create `jurnal_harian`
- [ ] Model: `JurnalHarian`
- [ ] Seeder: update `PerusahaanSeeder`
- [ ] Seeder: create `JurnalHarianSeeder`
- [ ] Test migration di development

### Sprint 5 (Jurnal)
- [ ] Controller: `JurnalHarianController`
- [ ] Request: `StoreJurnalHarianRequest`, `UpdateJurnalHarianRequest`
- [ ] Resource: `JurnalHarianResource`
- [ ] Routes: jurnal endpoints (8 endpoints)
- [ ] Policy/Authorization: siswa owner + admin
- [ ] Feature tests: CRUD + authorization
- [ ] API documentation

### Sprint 6 (Penilaian)
- [ ] Migration: 4 tabel penilaian (dengan field tambahan)
- [ ] Models: `AktivasiPenilaian`, `PenilaianBulanan`, `PenilaianKompetensi`, `PenilaianSikap`
- [ ] Controller: `PenilaianBulananController`
- [ ] Helper: Haversine distance calculation
- [ ] Endpoint: `generate-token`
- [ ] Endpoint: `verifikasi` (with token + GPS)
- [ ] Feature tests: token flow + GPS validation
- [ ] API documentation

---

**Status:** 🟡 In Progress  
**Last Updated:** 2026-11-05  
**Next Review:** Setelah Phase 4 selesai
