# Rencana Sprint 1 — Data Master Core

**Status:** Rencana disetujui, siap eksekusi
**Periode:** 14–20 Oktober 2026 (5 hari)
**Prasyarat:** Sprint 0 (auth) selesai & merged ke `develop`; branch kerja `feature/*` / `docs/*`
**Goal:** 7 tabel master + CRUD + Seeder + fondasi Excel. Foundation untuk semua modul lain.

---

## 1. Latar Belakang & Keputusan

Sprint 1 mengimplementasikan bagian "Manajemen Data Master" dari `backend-roadmap.md` (Task 1.1–1.7) mengikuti skema di `002-database-schema.md` (Phase 2) dan endpoint di `003-api-endpoints.md` (bagian 3–8).

| Topik | Keputusan |
|---|---|
| Otorisasi | Middleware `role:admin` untuk route **write**; read cukup `auth:sanctum` (semua role login) — konsisten Sprint 0, tanpa Policy |
| Seeder jurusan | Data asli SMKN 1 Magelang (10 program keahlian, dari menu Akademik situs resmi sekolah) |
| Seeder kompetensi | Minimal 5 per jurusan (50+ baris) |
| Bulk upload | **Hanya fondasi** di Sprint 1 (B6); endpoint `POST /kompetensi/bulk-upload` & `POST /perusahaan/bulk-upload` ditunda ke Sprint 2 |
| Library Excel | `maatwebsite/excel` v4.0.x (kompatibel Laravel 13: `illuminate/support ^12 \|\| ^13`, PHP ^8.3 — diverifikasi dari Packagist & docs resmi) |
| Hapus data berelasi | Andalkan FK (`CASCADE`/`RESTRICT`) + tangani `QueryException` → 422 `message` jelas (tanpa error code kustom baru) |
| Audit trail | Tanpa `created_by`/`updated_by` (kolom tidak ada di tech-spec) |
| Soft delete | Tidak dipakai di Sprint 1 (baru `siswa` di Sprint 2) |

**Catatan koreksi roadmap:** Task 1.1 roadmap menulis contoh jurusan "TKJ, RPL, MM, TKR, TBSM" — daftar itu **bukan** jurusan SMKN 1 Magelang. Data seeder memakai daftar resmi (§4.2).

---

## 2. Ruang Lingkup

### Termasuk

- 7 migration Phase 2 (urutan FK sesuai `002-database-schema.md` §Migration Order)
- 7 model + relasi + 7 seeder (data realistis)
- **32 endpoint** CRUD master + relasi jurusan–kompetensi & perusahaan–jurusan
- Fondasi bulk import Excel (library + pattern Import class + test parse)
- Feature test untuk setiap endpoint (happy path + authorization + validasi)
- Update dokumentasi (002, 003, API-Docs-fondasi, roadmap, Panduan Postman)

### Tidak Termasuk (Sprint 2+)

- Endpoint bulk-upload kompetensi & perusahaan (Sprint 2, Task 2.4)
- Tabel `guru`, `siswa` (Sprint 2)
- Filter `kuota_tersedia` perusahaan yang butuh data penempatan (diuji saat Sprint 3; query filter `jurusan_id` & `search` sudah jalan di Sprint 1)
- Template Excel download (opsional, Sprint 2)
- Dashboard admin (Sprint 3)

### Ringkasan Endpoint (32)

| # | Resource | Endpoint | Method |
|---|---|---|---|
| 3 | Jurusan | `/api/jurusan` | GET, POST |
| | | `/api/jurusan/{id}` | GET, PUT, DELETE |
| | | `/api/jurusan/{id}/kompetensi` | GET (nested) |
| 4 | Kompetensi | `/api/kompetensi` | GET, POST |
| | | `/api/kompetensi/{id}` | GET, PUT, DELETE |
| 5 | Gelombang | `/api/gelombang` | GET, POST |
| | | `/api/gelombang/{id}` | GET, PUT, DELETE |
| 6 | Perusahaan | `/api/perusahaan` | GET, POST |
| | | `/api/perusahaan/{id}` | GET, PUT, DELETE |
| | | `/api/perusahaan/{id}/jurusan` | POST (attach) |
| | | `/api/perusahaan/{id}/jurusan/{jurusanId}` | DELETE (detach) |
| 7 | Sikap | `/api/sikap` | GET, POST |
| | | `/api/sikap/{id}` | GET, PUT, DELETE |
| 8 | Waktu Absen | `/api/waktu-absen` | GET, POST |
| | | `/api/waktu-absen/{id}` | PUT, DELETE |

> `waktu-absen` tanpa `GET/{id}` (sesuai `003-api-endpoints.md` §8 — hanya 4 endpoint).

**Role:** GET = `any` (login), POST/PUT/DELETE = `admin`.

---

## 3. Strategi Git

Branch dibuat dari `develop`, merge kembali ke `develop` secara merge lokal (`--no-ff`), tanpa push/PR. Commit: `[BE] feat: ...` / `[BE] docs: ...`. Urutan:

| # | Branch | Isi | Merge ke |
|---|---|---|---|
| B0 | `docs/sprint-1-plan` | Dokumen rencana ini + panduan Postman Sprint 1 | develop |
| B1 | `feature/master-jurusan` | Migration + Model + Seeder + CRUD Jurusan | develop |
| B2 | `feature/master-kompetensi` | Migration + Model + Seeder + CRUD Kompetensi + nested route | develop |
| B3 | `feature/master-gelombang` | Migration + Model + Seeder + CRUD Gelombang | develop |
| B4 | `feature/master-perusahaan` | Migration (perusahaan + pivot) + Seeder + CRUD + attach/detach | develop |
| B5 | `feature/master-sikap-waktu-absen` | 2 resource kecil digabung (Sikap + Waktu Absen) | develop |
| B6 | `feature/excel-import-foundation` | Install maatwebsite/excel v4 + pattern import + test | develop |
| B7 | `docs/master-data` | Sinkronisasi dokumentasi + centang roadmap | develop |

> B2 menunggu B1 (FK jurusan); B4 menunggu B1 (pivot). B3, B5 independen — bisa diparalel bila perlu.

---

## 4. Tahap Pengerjaan

### B0 — `docs/sprint-1-plan`

- Dokumen ini: `.agents/tasks/sprint-1-master-data-plan.md`
- Panduan eksekusi Postman: `.agents/tasks/postman-master-data-execution.md`
- Commit: `[BE] docs: rencana Sprint 1 data master & panduan postman`

### B1 — `feature/master-jurusan` (0.5 hari)

**Migration** `create_jurusan_table`:
- `id`, `nama` varchar(100), `kode` varchar(20) nullable, timestamps

**Model** `Jurusan`: `fillable = [nama, kode]`; relasi `hasMany(Kompetensi)`, `belongsToMany(Perusahaan)`.

**Seeder** `JurusanSeeder` — 10 program keahlian SMKN 1 Magelang (menu Akademik, smknegeri1magelang.sch.id):

| Kode | Nama |
|---|---|
| DPIB | Desain Permodelan dan Informasi Bangunan |
| TKB | Teknik Konstruksi Bangunan |
| TEL | Teknik Elektronika |
| TITL | Teknik Instalasi Tenaga Listrik |
| TPTU | Teknik Pendingin dan Tata Udara |
| TP | Teknik Pemesinan |
| TKR | Teknik Otomotif |
| WEL | Teknik Pengelasan |
| TJKT | Teknik Jaringan Komputer dan Telekomunikasi |
| DKV | Desain Komunikasi Visual |

**Request validation:**
- `StoreJurusanRequest` / `UpdateJurusanRequest`: `nama` required string max:100; `kode` nullable string max:20

**Resource** `JurusanResource`: `{id, nama, kode}`.

**Route** (`routes/api.php`, dalam pola group §5):
- `Route::apiResource('jurusan', JurusanController::class)` → read `any`, write `admin`

**Test** `tests/Feature/Master/JurusanTest.php`:
- admin create/update/delete sukses (201/200/200)
- list & detail bisa dibuka semua role login; 401 tamu; 403 guru/siswa saat write
- validasi 422: `nama` kosong
- list `?search=` filter nama/kode

**Commit:** `[BE] feat: CRUD data master jurusan`

### B2 — `feature/master-kompetensi` (0.5 hari)

**Migration** `create_kompetensi_table`:
- `jurusan_id` FK → jurusan (ON DELETE CASCADE), `nama` varchar(255), `kode` varchar(50) nullable, timestamps

**Model** `Kompetensi`: `belongsTo(Jurusan)`.

**Seeder** `KompetensiSeeder`: minimal 5 per jurusan (contoh TJKT: Konfigurasi Jaringan Kabel Terstruktur, Perakitan & Perbaikan Komputer, Administrasi Server, Instalasi Jaringan, Troubleshooting Jaringan) → 50+ baris.

**Validation:** `jurusan_id` required + `exists:jurusan,id`; `nama` required max:255; `kode` nullable max:50.

**Routes:**
- `Route::apiResource('kompetensi', KompetensiController::class)` (read any / write admin)
- `GET /api/jurusan/{jurusan}/kompetensi` → `JurusanController@kompetensi` (eager load, any role)

**Filter:** `GET /api/kompetensi?jurusan_id=X&search=...`

**Test:** CRUD + authorization + filter `jurusan_id` + nested route 404 bila jurusan tak ada.

**Commit:** `[BE] feat: CRUD data master kompetensi`

### B3 — `feature/master-gelombang` (0.5 hari)

**Migration** `create_gelombang_table`:
- `tahun_ajaran` varchar(20), `nama` varchar(100), `daftar_mulai` date, `daftar_selesai` date, `praktik_mulai` date, `praktik_selesai` date, timestamps

**Model** `Gelombang`: `casts = ['daftar_mulai' => 'date', 'daftar_selesai' => 'date', 'praktik_mulai' => 'date', 'praktik_selesai' => 'date']`.

**Seeder** `GelombangSeeder`: 2 gelombang — "Gelombang 1" 2026/2027 (daftar Nov 2026, praktik Jan–Jun 2027), "Gelombang 2" 2026/2027.

**Validation:** 4 tanggal required `date`; `daftar_selesai after_or_equal:daftar_mulai`; `praktik_selesai after_or_equal:praktik_mulai`.

**Test:** CRUD + 422 saat `daftar_selesai < daftar_mulai` (dan pasangan praktik).

**Commit:** `[BE] feat: CRUD data master gelombang`

### B4 — `feature/master-perusahaan` (1 hari)

**Migration 1** `create_perusahaan_table`:
- `nama` varchar(255), `alamat` text, `latitude` decimal(10,8) nullable, `longitude` decimal(11,8) nullable, `pimpinan` varchar(255) nullable, `telp` varchar(20) nullable, `email` varchar(255) nullable, `deskripsi` text nullable, `kuota` unsigned int nullable, timestamps

**Migration 2** `create_perusahaan_jurusan_table` (pivot, urutan alfabet):
- `perusahaan_id` FK CASCADE, `jurusan_id` FK CASCADE, timestamps, composite PK

**Model** `Perusahaan`: `belongsToMany(Jurusan)`.

**Seeder** `PerusahaanSeeder`: 12 perusahaan sample (industri Magelang/Sekitar Bowokan, kuota 5–15, koordinat realistis, `jurusan_ids` terpasang).

**Validation:**
- `nama`, `alamat` required; `latitude` numeric nullable; `longitude` numeric nullable; `email` nullable email; `kuota` nullable int min:0; `jurusan_ids` nullable array + `exists:jurusan,id` per item

**Endpoint:**
- CRUD (on create/update: `sync(jurusan_ids)` bila terisi)
- `POST /api/perusahaan/{id}/jurusan` body `{ jurusan_id }` → `attachWithoutDuplicates` → 200 `{message, data}`
- `DELETE /api/perusahaan/{id}/jurusan/{jurusanId}` → 200 `{message}`; bila tidak terpasang → 404
- `GET /api/perusahaan?jurusan_id=X&search=nama|alamat`

**Resource** `PerusahaanResource` sertakan `jurusan` (loaded).

**Test:** CRUD + attach/detach + detach non-existent 404 + filter + authorization + 422 `jurusan_ids` invalid.

**Commit:** `[BE] feat: CRUD data master perusahaan & relasi jurusan`

### B5 — `feature/master-sikap-waktu-absen` (0.5 hari)

**Sikap** — migration `create_sikap_table`: `nama` varchar(100), `deskripsi` text nullable.
- Seeder: Disiplin, Tanggung Jawab, Kerjasama, Inisiatif, Kejujuran, Ketelitian, Kreativitas, Kemandirian, Percaya Diri, Kepemimpinan
- Validation: `nama` required max:100; `deskripsi` nullable
- `SikapController` apiResource (read any / write admin)

**Waktu Absen** — migration `create_waktu_absen_table`: `mulai` time, `selesai` time, `keterangan` varchar(255) nullable.
- Seeder: 06:00:00–22:00:00 "Sesuai sistem yang lama"
- Validation: `mulai` required `date_format:H:i`; `selesai` required `date_format:H:i` + `after:mulai`; `keterangan` nullable max:255
- `WaktuAbsenController` — **4 endpoint saja** (`index`, `store`, `update`, `destroy`; tanpa `show` sesuai spec)

**Test (gabung 1 file per resource):** CRUD + authorization + validasi urutan jam.

**Commit:** `[BE] feat: CRUD data master sikap & waktu absen`

### B6 — `feature/excel-import-foundation` (1 hari)

- `composer require maatwebsite/excel` (v4.0.x — kompatibel Laravel 13; **hindari 3.1.x < 3.1.70** kena CVE-2026-84374)
- Struktur pattern import:
  - `app/Imports/Concerns/` — kontrak dasar (mis. loop baris + validasi per baris)
  - `app/Support/ImportResult.php` — DTO: `success: int`, `failed: int`, `errors: [{row, error}]`
  - Response format mengikuti `003-api-endpoints.md` §4: `{message, success, failed, errors:[{row, error}]}`
- **Belum ada endpoint** — hanya fondasi + 1 Import class contoh internal untuk test
- **Test** `tests/Feature/Import/ExcelImportTest.php`:
  - Buat file `.xlsx` sementara via PhpSpreadsheet di dalam test (tanpa fixture biner di repo)
  - Parse file valid → semua baris sukses
  - Campuran valid/invalid → `success`/`failed` akurat + `errors` menyebut nomor baris
  - File rusak/bukan xlsx → 422 `INVALID_FILE_TYPE` / 422 validation `file`

**Commit:** `[BE] feat: fondasi bulk import Excel`

### B7 — `docs/master-data` (0.5 hari)

- `002-database-schema.md`: 7 tabel Phase 2 ditandai ✅ di §Ringkasan Tabel
- `003-api-endpoints.md`: bagian 3–8 ditandai ✅, hitung ulang Summary (5 → 37 done)
- `docs/API-Docs-fondasi.md`: tambah bagian Data Master
- `.agents/tasks/backend-roadmap.md`: centang Task 1.1–1.7 + milestone 27 Okt (sebagian)
- `004-postman-collection-guide.md`: catat bahwa folder 03–08 dieksekusi via `postman-master-data-execution.md`
- `DatabaseSeeder.php`: register semua seeder baru (urutan: Role → User → Jurusan → Gelombang → Sikap → WaktuAbsen → Perusahaan → Kompetensi)

**Commit:** `[BE] docs: dokumentasi data master`

---

## 5. Pola Route & Konvensi Kode

### Struktur `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    // Read: semua role login
    Route::apiResource('jurusan', JurusanController::class)->only(['index', 'show']);
    Route::get('jurusan/{jurusan}/kompetensi', [JurusanController::class, 'kompetensi']);
    Route::apiResource('kompetensi', KompetensiController::class)->only(['index', 'show']);
    Route::apiResource('gelombang', GelombangController::class)->only(['index', 'show']);
    Route::apiResource('perusahaan', PerusahaanController::class)->only(['index', 'show']);
    Route::apiResource('sikap', SikapController::class)->only(['index', 'show']);
    Route::apiResource('waktu-absen', WaktuAbsenController::class)->only(['index']);

    // Write: admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('jurusan', JurusanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('kompetensi', KompetensiController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('gelombang', GelombangController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['store', 'update', 'destroy']);
        Route::post('perusahaan/{perusahaan}/jurusan', [PerusahaanController::class, 'attachJurusan']);
        Route::delete('perusahaan/{perusahaan}/jurusan/{jurusan}', [PerusahaanController::class, 'detachJurusan']);
        Route::apiResource('sikap', SikapController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('waktu-absen', WaktuAbsenController::class)->only(['store', 'update', 'destroy']);
    });
});
```

### Konvensi (ikuti Sprint 0)

| Aspek | Konvensi |
|---|---|
| Controller | `app/Http/Controllers/` flat (JurusanController, dst.) — resource controller |
| Form Request | `app/Http/Requests/Master/` (`StoreXRequest`, `UpdateXRequest`), `authorize()` → `$this->user()?->isAdmin()` |
| Resource | `app/Http/Resources/XResource.php` |
| Response sukses create | `{message, data}` 201; update `{message, data}` 200; list/detail `{data}` (Resource collection → `links`/`meta` otomatis) |
| Hapus | 200 `{message: "X berhasil dihapus."}` |
| Hapus FK error | catch `QueryException` → 422 `{message: "Data tidak dapat dihapus karena masih digunakan..."}` |
| Test | `tests/Feature/Master/XTest.php`, `RefreshDatabase`, helper `admin()`/`guru()` seperti `RegisterTest` |
| Seeder | Tunggal per tabel, di-register di `DatabaseSeeder` (B7) |

---

## 6. Verifikasi (per branch)

1. `php artisan test` — semua test hijau (baseline 31 test Sprint 0 + test baru)
2. `./vendor/bin/pint` — hanya file yang disentuh
3. `php artisan route:list --path=api` — route sesuai §2 & §5
4. `php artisan migrate:fresh --seed` (MySQL lokal) — seeder jalan tanpa error

Akhir Sprint 1 (semua merged ke `develop`):
- 32 endpoint master berfungsi, teruji, terdokumentasi
- `git status` bersih; semua test hijau

---

## 7. Definisi Selesai (Definition of Done)

- [ ] B0 rencana + panduan Postman merged
- [ ] B1 Jurusan merged (migration + model + seeder 10 jurusan + CRUD + test)
- [ ] B2 Kompetensi merged (termasuk route nested `GET /jurusan/{id}/kompetensi`)
- [ ] B3 Gelombang merged (validasi urutan tanggal teruji)
- [ ] B4 Perusahaan merged (pivot + attach/detach + filter teruji)
- [ ] B5 Sikap + Waktu Absen merged
- [ ] B6 Fondasi Excel merged (parse valid + invalid rows teruji)
- [ ] B7 Dokumentasi sinkron (002, 003, API-Docs, roadmap, Panduan Postman)
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
