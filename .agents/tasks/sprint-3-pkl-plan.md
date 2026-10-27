# Rencana Sprint 3 — Pengajuan & Penempatan PKL

**Status:** Rencana disetujui, siap eksekusi
**Periode:** 28 Oktober – 3 November 2026 (5 hari)
**Prasyarat:** Sprint 2 selesai & merged ke `develop`; tabel `guru`, `siswa`, `perusahaan`, `gelombang`, `jurusan` + onboarding sudah berjalan
**Goal:** Alur PKL lengkap: siswa ajukan tempat PKL → admin/guru approve → penempatan aktif, dilengkapi dashboard admin dan hutang Sprint 1/2 yang ditunda (siswa-bimbingan, lokasi-pkl, kuota perusahaan, bulk upload kompetensi/perusahaan).

---

## 1. Latar Belakang & Keputusan

Sprint 3 mengimplementasikan `backend-roadmap.md` Task 3.1–3.4. Requirement baru: siswa yang sudah konfirmasi profil mengajukan perusahaan PKL; admin/guru menyetujui; sistem otomatis membuat penempatan (`lokasi_pkl`) dan menugaskan guru pembimbing.

| Topik | Keputusan |
|---|---|
| Gate onboarding siswa | Middleware `EnsureSiswaProfileConfirmed` (`profil.confirmed`) **dipasang** pada `POST /api/pengajuan-pkl` → 403 `PROFIL_BELUM_KONFIRMASI` |
| Validasi pengajuan (`POST /pengajuan-pkl`) | 1) Gelombang aktif (`GELOMBANG_NOT_ACTIVE`)<br>2) Siswa belum punya `lokasi_pkl` active (`SISWA_ALREADY_PLACED`)<br>3) Siswa belum punya pengajuan `pending` di gelombang tsb (`PENGAJUAN_DUPLICATE`)<br>4) Jurusan siswa diterima perusahaan (pivot `perusahaan_jurusan`)<br>5) Kuota perusahaan belum penuh (`PERUSAHAAN_KUOTA_PENUH`) |
| Perhitungan kuota perusahaan | Terpakai = jumlah `lokasi_pkl` pada perusahaan & gelombang dengan status `in ('pending','active')`. Jika `perusahaan.kuota !== null` dan `terpakai >= kuota` → `PERUSAHAAN_KUOTA_PENUH` |
| Auto-create `lokasi_pkl` saat approve | Dalam DB transaction: update pengajuan (`status='approved'`, `verified_by`, `verified_at`) + buat `lokasi_pkl` (`status='active'`, `guru_id` dari request opsional) |
| Aturan satu penempatan aktif | Satu siswa hanya 1 `lokasi_pkl` dengan `status='active'`; pelanggaran → `422 SISWA_ALREADY_PLACED` |
| Transisi status `lokasi_pkl` | `pending` → `active`/`cancelled`; `active` → `completed`/`cancelled`. Transisi lain ditolak `422` |
| Notifikasi approve/reject | Tabel `notifications` (standar Laravel) + `PengajuanPklReviewedNotification` (channel `database`); endpoint baca notifikasi tetap Sprint 7 |
| Dashboard admin | `GET /api/dashboard/admin` di-cache 10 menit; `presensi_hari_ini` placeholder sampai Sprint 4 (tabel `absensi_siswa` belum ada) |
| Siswa bimbingan guru | `presensi_hari_ini` & `jurnal_hari_ini` = `null` sampai Sprint 4/5 |

---

## 2. Ruang Lingkup

### Termasuk

- Migration/model/seeder `pengajuan_pkl` + `lokasi_pkl` + `notifications`
- **16 endpoint** Sprint 3 (Pengajuan 7, Lokasi PKL 6, Guru siswa-bimbingan 1, Siswa lokasi-pkl 1, Dashboard admin 1)
- Business logic: validasi gelombang, kuota, jurusan, duplicate, placed, transisi status
- Auto-create `lokasi_pkl` dari approve + bulk approve
- Database notification ke siswa saat approve/reject
- Filter `kuota_tersedia` pada `GET /api/perusahaan`
- Bulk upload kompetensi & perusahaan (carry-over Sprint 1/2)
- Feature test setiap endpoint + skenario error + notifikasi
- Update dokumentasi + Postman

### Tidak Termasuk / Ditunda

- `GET /api/siswa/{id}/lokasi-pkl` detail presensi/jurnal (Sprint 4/5)
- Dashboard guru (`GET /api/dashboard/guru/{guruId}`) & siswa (`GET /api/dashboard/siswa/me`) → Sprint 7 (butuh data presensi/jurnal/penilaian)
- Rekap Excel → Sprint 7
- Endpoint transaksional presensi/jurnal/penilaian → Sprint 4+

### Ringkasan Endpoint (16)

| # | Resource | Endpoint | Method | Role |
|---|---|---|---|---|
| 11 | Pengajuan PKL | `/api/pengajuan-pkl` | GET, POST, DELETE | read admin/guru/siswa; write siswa |
| | | `/api/pengajuan-pkl/{id}` | GET | admin, guru, siswa (miliknya) |
| | | `/api/pengajuan-pkl/{id}/approve` | PUT | admin, guru |
| | | `/api/pengajuan-pkl/{id}/reject` | PUT | admin, guru |
| | | `/api/pengajuan-pkl/bulk-approve` | PUT | admin, guru |
| 12 | Lokasi PKL | `/api/lokasi-pkl` | GET, POST, PUT, DELETE | read admin/guru; write admin |
| | | `/api/lokasi-pkl/{id}` | GET, PUT, DELETE | admin (siswa miliknya untuk GET) |
| | | `/api/lokasi-pkl/{id}/status` | PUT | admin |
| 9 (lanjutan) | Guru | `/api/guru/{id}/siswa-bimbingan` | GET | admin, guru |
| 10 (lanjutan) | Siswa | `/api/siswa/{id}/lokasi-pkl` | GET | admin, guru, siswa (miliknya) |
| 17 (basic) | Dashboard | `/api/dashboard/admin` | GET | admin |

> **Route order:** `PUT /api/pengajuan-pkl/bulk-approve` **wajib** didaftarkan sebelum `/api/pengajuan-pkl/{pengajuan_pkl}`.

---

## 3. Strategi Git

Branch dari `develop`; merge lokal `--no-ff` ke `develop` (tanpa push/PR). Commit `[BE] feat: ...` / `[BE] docs: ...`.

| # | Branch | Isi | Merge ke |
|---|---|---|---|
| B0 | `docs/sprint-3-plan` | Dokumen rencana Sprint 3 | develop |
| B1 | `feature/pengajuan-pkl` | Migration + model + seeder + controller + notification + test | develop |
| B2 | `feature/lokasi-pkl` | Model + controller + siswa-bimbingan + lokasi-pkl + filter kuota + bulk upload kompetensi/perusahaan + test | develop |
| B3 | `feature/dashboard-admin` | Dashboard admin + cache + test | develop |
| B4 | `docs/sprint-3-pkl` | Sinkronisasi dokumentasi + Postman | develop |

> B2 bergantung B1 (tabel `lokasi_pkl` dibutuhkan `approve`). B3 independen dari B2 (hanya agregasi data). Bulk upload kompetensi/perusahaan (carry-over) dimasukkan ke B2.

---

## 4. Tahap Pengerjaan

### B0 — `docs/sprint-3-plan`

- Buat dokumen rencana ini (`.agents/tasks/sprint-3-pkl-plan.md`)
- Commit: `[BE] docs: rencana Sprint 3 pengajuan & penempatan PKL`

### B1 — `feature/pengajuan-pkl` (2 hari)

**Migration:**

- `create_pengajuan_pkl_table`: `siswa_id` FK → `siswa` (CASCADE), `perusahaan_id` FK → `perusahaan` (CASCADE), `gelombang_id` FK → `gelombang` (CASCADE), `status` enum `['pending','approved','rejected']` default `'pending'`, `alasan_penolakan` text nullable, `verified_by` FK → `users` (SET NULL) nullable, `verified_at` timestamp nullable, timestamps
- `create_lokasi_pkl_table` (dibuat sekalian di B1 agar transaksi approve utuh): `siswa_id` FK → `siswa` (CASCADE), `guru_id` FK → `guru` (SET NULL) nullable, `perusahaan_id` FK → `perusahaan` (CASCADE), `gelombang_id` FK → `gelombang` (CASCADE), `status` enum `['pending','active','completed','cancelled']` default `'pending'`, `catatan` text nullable, timestamps
- `create_notifications_table` (standar Laravel) untuk database notification ke siswa saat approve/reject

**Model:**

- `PengajuanPkl`: `fillable = [siswa_id, perusahaan_id, gelombang_id, status, alasan_penolakan, verified_by, verified_at]`; relasi `belongsTo(Siswa)`, `belongsTo(Perusahaan)`, `belongsTo(Gelombang)`, `belongsTo(User, 'verified_by')`
- Relasi balik di `Siswa`: `hasMany(PengajuanPkl)`, `hasMany(LokasiPkl)`
- Cast `verified_at` → datetime

**Request:**

- `StorePengajuanPklRequest`: `perusahaan_id` required exists:perusahaan; `gelombang_id` required exists:gelombang
- `ApprovePengajuanPklRequest`: `guru_pembimbing_id` nullable exists:guru
- `RejectPengajuanPklRequest`: `alasan_penolakan` required string max:1000
- `BulkApprovePengajuanPklRequest`: `ids` required array min:1; `ids.*` required exists:pengajuan_pkl,id; `guru_pembimbing_id` nullable exists:guru

**Resource:** `PengajuanPklResource` (siswa, perusahaan, gelombang, status, alasan_penolakan, verified_by, verified_at, timestamps)

**Logic (controller):**

- `index`: admin/guru lihat semua (filter `status`, `siswa_id`, `perusahaan_id`, `gelombang_id`, paginasi); siswa hanya miliknya
- `show`: siswa hanya miliknya (403 jika bukan)
- `store` (siswa, middleware `profil.confirmed`): validasi gelombang aktif → siswa belum placed → belum ada pengajuan pending di gelombang → jurusan sesuai → kuota tersedia → create pengajuan `pending`
- `approve`: transaksi → update pengajuan `approved` + create `lokasi_pkl` (`status='active'`, `guru_id` dari request); kirim notifikasi `pengajuan-approved` ke siswa
- `reject`: update pengajuan `rejected` + `alasan_penolakan`; kirim notifikasi `pengajuan-rejected`
- `bulkApprove`: loop approve dalam transaksi tunggal; error per ID dilaporkan
- `destroy` (siswa): hanya jika `status='pending'` dan miliknya; else 422
- Error code: `PENGAJUAN_DUPLICATE`, `GELOMBANG_NOT_ACTIVE`, `PERUSAHAAN_KUOTA_PENUH`, `SISWA_ALREADY_PLACED`

**Notification:** `PengajuanPklReviewedNotification` (`ShouldQueue` = false di PAD 1, channel `database`)

**Seeder:** `PengajuanPklSeeder` (beberapa sample pending/approved/rejected, konsisten dengan `LokasiPklSeeder`)

**Test** `tests/Feature/Pkl/PengajuanPklTest.php`:
- Full flow: pengajuan → approve → lokasi_pkl terbuat + notifikasi tersimpan
- Reject + cancel
- Bulk approve
- Error code: `GELOMBANG_NOT_ACTIVE`, `PENGAJUAN_DUPLICATE`, `PERUSAHAAN_KUOTA_PENUH`, `SISWA_ALREADY_PLACED`, jurusan tidak sesuai, cancel non-pending ditolak
- Gate `PROFIL_BELUM_KONFIRMASI` pada `POST /pengajuan-pkl` (siswa belum konfirmasi → 403)
- Otorisasi: siswa tidak bisa approve/reject; siswa lain tidak bisa cancel pengajuan milik orang lain

**Commit:** `[BE] feat: pengajuan PKL, approval & notifikasi`

### B2 — `feature/lokasi-pkl` (1.5 hari)

**Model:**

- `LokasiPkl`: `fillable = [siswa_id, guru_id, perusahaan_id, gelombang_id, status, catatan]`; relasi `belongsTo(Siswa)`, `belongsTo(Guru)`, `belongsTo(Perusahaan)`, `belongsTo(Gelombang)`
- Tambah `Guru::lokasiPkl()` (hasMany), `Siswa::lokasiPkl()` (hasMany), `Siswa::pengajuanPkl()` (hasMany)

**Request:**

- `StoreLokasiPklRequest`: `siswa_id` required exists:siswa; `perusahaan_id` required exists:perusahaan; `guru_id` nullable exists:guru; `gelombang_id` required exists:gelombang; `catatan` nullable
- `UpdateLokasiPklRequest`: sama, semua opsional kecuali validasi exists
- `UpdateStatusLokasiPklRequest`: `status` required in:pending,active,completed,cancelled

**Resource:** `LokasiPklResource` (siswa, guru, perusahaan, gelombang, status, catatan, timestamps)

**Controller `LokasiPklController`:**

- `index`: filter `status`, `siswa_id`, `guru_id`, `perusahaan_id`, `gelombang_id`, `search` (nama siswa), paginasi
- `show`: admin/guru semua; siswa hanya miliknya
- `store`: validasi siswa belum punya lokasi `active` → create (`status` default `pending`)
- `update`: update `guru_id`, `perusahaan_id`, `gelombang_id`, `catatan`
- `updateStatus`: validasi transisi status (lihat §1); jika transisi ke `active` dan siswa sudah punya lokasi `active` lain → `SISWA_ALREADY_PLACED`
- `destroy`: hapus penempatan

**Controller lanjutan Sprint 2:**

- `GuruController@siswaBimbingan` (`GET /api/guru/{id}/siswa-bimbingan`): daftar siswa bimbingan dari `lokasi_pkl` (status `active`); `presensi_hari_ini` & `jurnal_hari_ini` = `null` (Sprint 4/5)
- `SiswaController@lokasiPkl` (`GET /api/siswa/{id}/lokasi-pkl`): admin/guru semua; siswa hanya miliknya
- `PerusahaanController@index`: tambah filter `kuota_tersedia` (boolean) — perusahaan yang kuota belum penuh

**Bulk upload carry-over (Sprint 1/2):**

- `KompetensiImport` + `POST /api/kompetensi/bulk-upload` (kolom: `jurusan_kode`, `nama`, `kode`)
- `PerusahaanImport` + `POST /api/perusahaan/bulk-upload` (kolom: `nama`, `alamat`, `latitude`, `longitude`, `pimpinan`, `telp`, `email`, `deskripsi`, `kuota`, `jurusan_kode`)

**Seeder:** `LokasiPklSeeder` (penempatan sample konsisten dengan `PengajuanPklSeeder`)

**Test** `tests/Feature/Pkl/LokasiPklTest.php`:
- CRUD + otorisasi
- Validasi `SISWA_ALREADY_PLACED` saat store & updateStatus
- Transisi status valid/invalid
- `GET /api/guru/{id}/siswa-bimbingan`
- `GET /api/siswa/{id}/lokasi-pkl` (admin + siswa miliknya; siswa lain → 403)
- Filter `kuota_tersedia` pada `GET /api/perusahaan`
- Bulk upload kompetensi & perusahaan (valid/invalid/baris gagal)

**Commit:** `[BE] feat: lokasi PKL, siswa bimbingan & filter kuota perusahaan`

### B3 — `feature/dashboard-admin` (0.5 hari)

**Controller:** `DashboardController@admin` (`GET /api/dashboard/admin`, role `admin`)

**Logic:**
- `total_siswa` = `Siswa::count()`
- `total_guru` = `Guru::count()`
- `total_perusahaan` = `Perusahaan::count()`
- `siswa_by_status` = jumlah `LokasiPkl` per status (`pending`, `active`, `completed`) — siswa tanpa lokasi masuk kategori `pending`
- `pengajuan_pending` = `PengajuanPkl::where('status','pending')->count()`
- `presensi_hari_ini` = `{ "hadir": 0, "izin": 0, "sakit": 0, "tidak_absen": 0 }` (placeholder sampai Sprint 4)
- Dibungkus `Cache::remember('dashboard.admin', 600, ...)`

**Test** `tests/Feature/Dashboard/DashboardAdminTest.php`:
- Struktur respons sesuai kontrak
- Akurasi angka terhadap data seeder/factory
- Cache bekerja (request kedua tidak hit DB — uji via mock/counter)
- Otorisasi: non-admin → 403

**Commit:** `[BE] feat: endpoint dashboard admin dengan cache`

### B4 — `docs/sprint-3-pkl` (0.5 hari)

1. Update `.agents/tech-specs/2026/002-database-schema.md` (tandai `pengajuan_pkl`, `lokasi_pkl`, `notifications` ✅; total 14 dari 20 tabel)
2. Update `.agents/tech-specs/2026/003-api-endpoints.md` (tandai §9, §10, §11, §12, §17.1 ✅; update Summary 57 → 73 done; §17.2 & §17.3 tetap 📋)
3. Update `.agents/tasks/backend-roadmap.md` (centang Task 3.1–3.4 + item Sprint 2 yang ditunda ke Sprint 3: `siswa-bimbingan`, `lokasi-pkl`, `kuota_tersedia`)
4. Update `docs/API-Docs-fondasi.md` (bagian Pengajuan PKL, Lokasi PKL, Dashboard Admin)
5. Buat `.agents/tasks/postman-sprint3-execution.md` (format per-request seperti `postman-sprint2-execution.md`)
6. Update `DatabaseSeeder.php` (daftarkan `PengajuanPklSeeder`, `LokasiPklSeeder`)
7. Commit: `[BE] docs: dokumentasi pengajuan, lokasi PKL & dashboard admin`

---

## 5. Pola Route & Konvensi

```php
Route::middleware('auth:sanctum')->group(function () {
    // Onboarding siswa (Sprint 2)
    Route::middleware('role:siswa')->group(function () {
        Route::get('siswa/me', [SiswaController::class, 'me']);
        Route::put('siswa/me/profil', [SiswaController::class, 'updateProfil']);
        Route::put('siswa/me/konfirmasi', [SiswaController::class, 'konfirmasi']);
    });

    // Read (admin/guru)
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('guru', [GuruController::class, 'index']);
        Route::get('guru/{guru}', [GuruController::class, 'show']);
        Route::get('siswa', [SiswaController::class, 'index']);
        Route::get('siswa/{siswa}', [SiswaController::class, 'show']);
        Route::get('siswa/{siswa}/lokasi-pkl', [SiswaController::class, 'lokasiPkl']);
        Route::get('guru/{guru}/siswa-bimbingan', [GuruController::class, 'siswaBimbingan']);
        Route::get('pengajuan-pkl', [PengajuanPklController::class, 'index']);
        Route::get('pengajuan-pkl/{pengajuan_pkl}', [PengajuanPklController::class, 'show']);
        Route::get('lokasi-pkl', [LokasiPklController::class, 'index']);
        Route::get('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'show']);
    });

    // Siswa transaksional
    Route::middleware(['role:siswa', 'profil.confirmed'])->group(function () {
        Route::post('pengajuan-pkl', [PengajuanPklController::class, 'store']);
        Route::delete('pengajuan-pkl/{pengajuan_pkl}', [PengajuanPklController::class, 'destroy']);
    });

    // Admin/guru write
    Route::middleware('role:admin,guru')->group(function () {
        Route::put('pengajuan-pkl/{pengajuan_pkl}/approve', [PengajuanPklController::class, 'approve']);
        Route::put('pengajuan-pkl/{pengajuan_pkl}/reject', [PengajuanPklController::class, 'reject']);
        Route::put('pengajuan-pkl/bulk-approve', [PengajuanPklController::class, 'bulkApprove']); // SEBELUM {id}
    });

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::post('lokasi-pkl', [LokasiPklController::class, 'store']);
        Route::put('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'update']);
        Route::put('lokasi-pkl/{lokasi_pkl}/status', [LokasiPklController::class, 'updateStatus']);
        Route::delete('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'destroy']);
        Route::get('dashboard/admin', [DashboardController::class, 'admin']);
    });
});
```

| Aspek | Konvensi |
|---|---|
| Controller | `PengajuanPklController`, `LokasiPklController`, `DashboardController` |
| Request | `app/Http/Requests/Pkl/` (`StorePengajuanPklRequest`, `ApprovePengajuanPklRequest`, `RejectPengajuanPklRequest`, `BulkApprovePengajuanPklRequest`, `StoreLokasiPklRequest`, `UpdateLokasiPklRequest`, `UpdateStatusLokasiPklRequest`) |
| Resource | `PengajuanPklResource`, `LokasiPklResource` |
| Notification | `App\Notifications\PengajuanPklReviewedNotification` |
| Response | create `{message, data}` 201; approve/reject `{message, data}` 200; list `{data, links, meta}` |
| Delete | 200 `{message: "X berhasil dihapus."}` |
| Test | `tests/Feature/Pkl/*`, `tests/Feature/Dashboard/*`, `RefreshDatabase` |

---

## 6. Error Codes Sprint 3

| Code | HTTP | Kapan |
|---|---|---|
| `GELOMBANG_NOT_ACTIVE` | 422 | Pengajuan saat gelombang tidak dalam periode pendaftaran |
| `PENGAJUAN_DUPLICATE` | 422 | Siswa sudah punya pengajuan `pending` di gelombang tsb |
| `PERUSAHAAN_KUOTA_PENUH` | 422 | Kuota perusahaan sudah penuh |
| `SISWA_ALREADY_PLACED` | 422 | Siswa sudah punya `lokasi_pkl` active |
| `PROFIL_BELUM_KONFIRMASI` | 403 | Siswa belum konfirmasi profil saat mengajukan PKL |

---

## 7. Verifikasi (per branch)

1. `php artisan test` — semua test hijau (baseline 213 + test baru Sprint 3)
2. `./vendor/bin/pint` — hanya file yang disentuh
3. `php artisan route:list --path=api` — `bulk-approve` muncul sebelum `{pengajuan_pkl}`; `siswa/me*` sebelum `siswa/{id}`
4. `php artisan migrate:fresh --seed` (MySQL) — seeder jalan; data pengajuan & lokasi PKL sample tersedia
5. Cek manual via Postman (lihat `postman-sprint3-execution.md` setelah B4)

---

## 8. Definisi Selesai (DoD)

- [ ] B1 Pengajuan PKL merged (migration + model + seeder + controller + notification + test)
- [ ] B2 Lokasi PKL merged (model + controller + siswa-bimbingan + lokasi-pkl + filter kuota + bulk upload kompetensi/perusahaan + test)
- [ ] B3 Dashboard Admin merged (endpoint + cache + test)
- [ ] B4 Dokumentasi + Postman sinkron
- [ ] Semua test hijau, pint bersih, `migrate:fresh --seed` sukses, `git status` bersih
- [ ] `PROFIL_BELUM_KONFIRMASI` gate aktif pada `POST /api/pengajuan-pkl`
- [ ] Auto-create `lokasi_pkl` saat approve/bulk-approve berjalan dalam transaksi
- [ ] Error code kustom (`PENGAJUAN_DUPLICATE`, `GELOMBANG_NOT_ACTIVE`, `PERUSAHAAN_KUOTA_PENUH`, `SISWA_ALREADY_PLACED`, `PROFIL_BELUM_KONFIRMASI`) teruji

---

Apakah rencana Sprint 3 ini sudah sesuai, atau ada poin yang ingin Anda sesuaikan (misalnya apakah `POST /kompetensi/bulk-upload` & `POST /perusahaan/bulk-upload` ingin sekalian dimasukkan ke Sprint 3, atau status awal `lokasi_pkl` saat approve)? Jika sudah setuju, silakan pindah ke **Build mode** agar kita bisa mulai dari pembuatan file rencana (`B0`) atau langsung ke implementasi (`B1`).