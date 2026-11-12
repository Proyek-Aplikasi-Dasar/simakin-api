# Execution Plan: Bulk Upload Akun (Users) oleh Admin

**Branch:** `docs/akun-bulk-upload-plan`  
**Created:** 12 November 2026  
**Basis:** Mockup admin akun & peran (`docs/mockups/web/admin/pengguna/akun-peran/`), existing bulk upload pattern (GuruImport, SiswaImport), backend-roadmap.md, 003-api-endpoints.md  
**Referensi:** `.agents/Catatan_FE_untuk_BE_Simakin.md` (gap #5: Akun - nomor telepon, Login Terakhir, Wajib ganti password, 6 checkbox hak akses)

---

## Ringkasan Eksekutif

Saat ini, bulk upload sudah tersedia untuk: Jurusan, Kompetensi, Perusahaan, Guru, dan Siswa. Namun **bulk upload untuk Akun (Users) belum diimplementasikan**. Admin hanya bisa membuat akun satu per satu via `POST /api/register` atau `POST /api/users` (tidak ada endpoint create user langsung, hanya via register).

Berdasarkan mockup admin "Akun & Peran", admin bisa:
1. Tambah akun baru (nama, username, password, role, status aktif)
2. Edit akun (nama, username, role, status)
3. Reset password
4. Hapus akun (soft delete)
5. Toggle aktif/nonaktif

Bulk upload akun akan memungkinkan admin mengimpor banyak akun sekaligus via file Excel, mirip dengan bulk upload guru/siswa.

---

## Analisis Mockup Admin Akun & Peran

### Mockup yang Direview
- `docs/mockups/web/admin/pengguna/akun-peran/admin-akun-dan-peran.png` — List akun
- `docs/mockups/web/admin/pengguna/akun-peran/admin-akun-dan-peran-form-tambah-edit.png` — Form tambah/edit
- `docs/mockups/web/admin/pengguna/akun-peran/admin-form-akun-dan-peran-tambah-akun-new.png` — Form tambah (new)
- `docs/mockups/web/admin/pengguna/akun-peran/admin-form-akun-dan-peran-edit-akun-new.png` — Form edit (new)
- `docs/mockups/web/admin/pengguna/akun-peran/admin-form-akun-dan-peran-validasi-tambah-edit-akun-new.png` — Validasi

### Temuan dari Mockup
1. **Form Tambah Akun** (admin-form-akun-dan-peran-tambah-akun-new.png):
   - Field: Nama Lengkap, NIP/NIS, Email, Nomor Telepon, Peran Pengguna (dropdown: Admin/Guru/Siswa)
   - NIP/NIS otomatis menjadi username dan password awal
   - Tombol: "Simpan"

2. **Form Edit Akun** (admin-form-akun-dan-peran-edit-akun-new.png):
   - Field: Nama Lengkap, NIP/NIS, Email, Nomor Telepon, Peran Pengguna (dropdown)
   - Password tidak ditampilkan (reset terpisah via PUT /api/users/{id}/password)
   - Tombol: "Simpan Perubahan"

3. **Validasi** (admin-form-akun-dan-peran-validasi-tambah-edit-akun-new.png):
   - NIP/NIS unik (menjadi username)
   - Peran Pengguna wajib dipilih
   - Email format valid (opsional)

4. **List Akun** (admin-akun-dan-peran.png):
   - Kolom: Nama, Username, Role, Status (Aktif/Nonaktif), Aksi (Edit/Hapus/Reset Password)
   - Filter: Role, Status, Search

### Gap dari FE (Catatan_FE_untuk_BE_Simakin.md)
- **Akun:** nomor telepon, "Login Terakhir", "Wajib ganti password", dan 6 checkbox hak akses (tidak ada tabel permission)
- **Status:** Gap ini di-defer ke PAD 2 (tidak blocking untuk bulk upload)

---

## Analisis Kebutuhan

### Apa yang Sudah Ada
| Komponen | Status | Keterangan |
|---|---|---|
| Tabel `users` | ✅ Ada | id, name, username, email, password, role_id, deleted_at |
| Tabel `roles` | ✅ Ada | id, nama (admin, guru, siswa) |
| UserController | ✅ Ada | index, show, update, resetPassword, destroy |
| Form Request | ✅ Ada | UpdateUserRequest, ResetPasswordRequest |
| UserResource | ✅ Ada | Response format |
| Bulk upload pattern | ✅ Ada | GuruImport, SiswaImport sebagai referensi |
| Template Excel | ✅ Ada | TemplateController + public/templates/ |

### Apa yang Belum Ada
| Komponen | Status | Keterangan |
|---|---|---|
| `POST /api/users/bulk-upload` | ❌ Belum | Endpoint bulk upload akun |
| `UserImport` class | ❌ Belum | Import class untuk bulk upload |
| `template-akun.xlsx` | ❌ Belum | Template Excel untuk bulk upload |
| Route registration | ❌ Belum | Route `users/bulk-upload` |
| `StoreUserRequest` (optional) | ❌ Belum | Form request untuk create user (saat ini pakai RegisterRequest) |

---

## Desain Import Class (UserImport)

### Kolom Excel yang Dibutuhkan
| Kolom | Wajib | Keterangan | Validasi |
|---|---|---|---|
| `nama` | ✅ | Nama Lengkap | required, string, max:255 |
| `nip_nis` | ✅ | NIP (guru) / NIS (siswa) / Username (admin) | required, string, max:255, unique:users,username |
| `email` | ❌ | Email (opsional) | nullable, email, unique:users,email |
| `telp` | ❌ | Nomor Telepon (opsional) | nullable, string, max:20 |
| `role` | ✅ | Peran Pengguna (admin/guru/siswa) | required, in:admin,guru,siswa |

### Aturan Username & Password Awal
- **Guru:** username = NIP, password awal = NIP
- **Siswa:** username = NIS, password awal = NIS
- **Admin:** username = nilai kolom `nip_nis`, password awal = nilai kolom `nip_nis`

> NIP/NIS otomatis menjadi username dan password awal. Tidak perlu kolom terpisah untuk username dan password.

### Catatan Penting
1. **UserImport hanya membuat akun `users`** — tidak membuat profil guru/siswa (karena role bisa admin/guru/siswa). Jika role = guru, profil guru dibuat terpisah via GuruImport. Jika role = siswa, profil siswa dibuat terpisah via SiswaImport.

2. **Password awal:** NIP/NIS otomatis menjadi password awal. Tidak perlu kolom `password` terpisah.

3. **Role resolution:** Kolom `role` berisi nama role (admin/guru/siswa), bukan role_id. Import class perlu resolve nama → role_id.

4. **Username unique:** Validasi unique di `users.username`.

5. **Kolom `telp`:** Tidak disimpan di tabel `users` (tidak ada kolom telp). Kolom ini hanya untuk referensi dokumentasi. Jika perlu menyimpan telp, gunakan GuruImport/SiswaImport yang menyimpan telp di tabel profil.

6. **Kolom `nip_nis`:** Untuk admin, isi dengan username yang diinginkan (bukan NIP/NIS). Untuk guru, isi dengan NIP. Untuk siswa, isi dengan NIS.

5. **Email unique:** Validasi unique di `users.email` (jika diisi).

6. **Soft delete:** Jika username sudah ada tapi soft-deleted, pertimbangkan untuk restore atau tolak (rekomendasi: tolak dengan error).

---

## Implementasi Detail

### 1. Migration (Tidak Diperlukan)
Tidak ada perubahan schema. Tabel `users` sudah ada dengan kolom yang cukup.

### 2. Model User (Tidak Diperlukan)
Model `User` sudah ada dengan fillable yang cukup.

### 3. Import Class: `UserImport`

**File:** `app/Imports/UserImport.php`

**Pattern:** Ikuti `GuruImport.php` dan `SiswaImport.php`

```php
<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements Import, WithHeadingRow
{
    use ImportsRows;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nip_nis' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'telp' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['admin', 'guru', 'siswa'])],
        ];
    }

    protected function saveRow(array $data): void
    {
        $role = Role::where('nama', strtolower(trim($data['role'])))->first();

        if (! $role) {
            throw new \RuntimeException('Role tidak ditemukan: ' . $data['role']);
        }

        $nipNis = (string) $data['nip_nis'];

        DB::transaction(function () use ($data, $role, $nipNis) {
            User::create([
                'name' => (string) $data['nama'],
                'username' => $nipNis,
                'email' => $data['email'] ?? null,
                'password' => $nipNis,
                'role_id' => $role->id,
            ]);
        });
    }

    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nip_nis.required' => 'NIP/NIS wajib diisi.',
            'nip_nis.unique' => 'Username sudah terdaftar.',
            'role.required' => 'Peran Pengguna wajib diisi.',
            'role.in' => 'Peran harus admin, guru, atau siswa.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'telp.max' => 'Nomor telepon maksimal 20 karakter.',
        ];
    }
}
```

### 4. Controller: Tambah method `bulkUpload` di UserController

**File:** `app/Http/Controllers/UserController.php`

Tambah method:
```php
public function bulkUpload(Request $request)
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
    ]);

    $result = (new UserImport)->import($request->file('file')->getRealPath());

    return response()->json($result->toArray());
}
```

### 5. Route Registration

**File:** `routes/api.php`

Tambah di dalam group admin:
```php
Route::post('users/bulk-upload', [UserController::class, 'bulkUpload']);
```

### 6. Template Excel

**File:** `public/templates/template-akun.xlsx`

Kolom header:
| Kolom | Keterangan | Contoh |
|---|---|---|
| `nama` | Nama Lengkap | Budi Santoso |
| `nip_nis` | NIP (guru) / NIS (siswa) / Username (admin) — otomatis jadi username & password awal | 198501012010011001 |
| `email` | Email (opsional) | budi@sekolah.id |
| `telp` | Nomor Telepon (opsional) | 081234567890 |
| `role` | Peran Pengguna (admin/guru/siswa) | guru |

### Aturan Pengisian Template
- Kolom `nip_nis`: Isi dengan NIP untuk guru, NIS untuk siswa, atau username custom untuk admin
- Kolom `nip_nis` otomatis menjadi username dan password awal
- Kolom `role`: isi dengan `admin`, `guru`, atau `siswa` (lowercase)
- Kolom `password` tidak diperlukan — password awal = nilai `nip_nis`

### 7. TemplateController Update

**File:** `app/Http/Controllers/TemplateController.php`

Tambah di TEMPLATES array:
```php
'akun' => 'template-akun.xlsx',
```

### 8. Dokumentasi API

**File:** `.agents/tech-specs/2026/003-api-endpoints.md`

Tambah di section 2 (User Management):
```
| POST | /api/users/bulk-upload | Required | admin | Import Excel |
```

### 9. Dokumentasi Backend Roadmap

**File:** `.agents/tasks/backend-roadmap.md`

Tambah task di Sprint 0 atau Sprint 1:
```
### Task X.X: Bulk Upload Akun (Users)
- [ ] Import class: UserImport
- [ ] Controller: bulkUpload method di UserController
- [ ] Route: POST /api/users/bulk-upload
- [ ] Template: template-akun.xlsx
- [ ] Test: bulk upload valid + invalid rows
```

---

## Mockup Review: Akun & Peran

### Temuan dari Mockup (admin-akun-dan-peran-tambah-akun-new.png)
1. Form tambah akun punya field: Nama Lengkap, NIP/NIS, Email, Nomor Telepon, Peran Pengguna
2. NIP/NIS otomatis menjadi username dan password awal
3. Role dropdown: Admin, Guru, Siswa
4. Password tidak perlu diisi manual — otomatis dari NIP/NIS

### Temuan dari Mockup (admin-akun-dan-peran.png)
1. List akun: Nama, Username, Role, Status, Aksi
2. Filter: Role, Status, Search
3. Bulk action: tidak terlihat di mockup (mungkin tidak ada bulk action di UI)

### Temuan dari Mockup (admin-form-akun-dan-peran-tambah-akun-new.png)
1. Form tambah akun: Nama Lengkap, NIP/NIS, Email, Nomor Telepon, Peran Pengguna
2. NIP/NIS otomatis menjadi username dan password awal
3. Role dropdown: Admin, Guru, Siswa
4. Password tidak perlu diisi manual — otomatis dari NIP/NIS

### Kesimpulan
- Mockup mendukung bulk upload secara tidak langsung (tidak ada UI bulk action, tapi endpoint bulk-upload bisa dipanggil dari tempat lain)
- Field yang dibutuhkan: nama, nip_nis, email (opsional), telp (opsional), role
- Status default: aktif (is_active = true)
- Password awal = NIP/NIS (otomatis, tidak perlu kolom password)

---

## Risiko & Mitigasi

| Risk | Impact | Mitigation |
|---|---|---|
| Role tidak valid (typo) | Row gagal | Validasi role in:admin,guru,siswa + pesan error jelas |
| Username duplikat | Row gagal | Validasi unique:users,username |
| Email duplikat | Row gagal | Validasi unique:users,email |
| User dibuat tanpa profil guru/siswa | User tidak bisa login sebagai guru/siswa | Catatan: UserImport hanya buat akun users. Profil guru/siswa dibuat terpisah via GuruImport/SiswaImport |
| Password lemah | Keamanan | Password = NIP/NIS. NIP biasanya >= 18 karakter (aman). NIS biasanya 5-10 karakter (mungkin < 8). Rekomendasi: validasi min:8 untuk guru (NIP), untuk siswa bisa lebih pendek karena NIS pendek. Admin bisa reset password jika perlu. |

---

## Checklist Progress

### Code
- [ ] Import class: `UserImport` (app/Imports/UserImport.php)
- [ ] Controller: `bulkUpload` method di UserController
- [ ] Route: `POST /api/users/bulk-upload`
- [ ] Template: `public/templates/template-akun.xlsx`

### TemplateController
- [ ] Tambah `'akun' => 'template-akun.xlsx'` di TEMPLATES array

### Dokumentasi
- [ ] Update `003-api-endpoints.md` (tambah endpoint bulk-upload di User Management)
- [ ] Update `backend-roadmap.md` (tambah task bulk upload akun)
- [ ] Update `PRD.md` (scope PAD 1: bulk upload akun)

### Testing
- [ ] Feature test: bulk upload valid rows
- [ ] Feature test: bulk upload invalid rows (duplikat username, role invalid)
- [ ] Feature test: authorization (hanya admin)

---

## Estimasi

| Task | Estimasi |
|---|---|
| Import class UserImport | 0.5 hari |
| Controller bulkUpload + route | 0.25 hari |
| Template Excel akun | 0.25 hari |
| TemplateController update | 0.1 hari |
| Dokumentasi | 0.25 hari |
| Testing | 0.5 hari |
| **Total** | **1.85 hari** |

---

## Catatan Penting

1. **UserImport hanya membuat akun `users`** — tidak membuat profil guru/siswa. Jika admin ingin membuat akun guru lengkap (dengan profil), gunakan GuruImport (yang otomatis buat users + guru). UserImport digunakan untuk membuat akun standalone (misalnya admin baru, atau akun yang tidak perlu profil guru/siswa).

2. **Konsistensi dengan existing pattern:** UserImport mengikuti pola GuruImport/SiswaImport (ImportsRows trait, WithHeadingRow, rules(), saveRow(), messages()).

3. **Username & Password Awal:** NIP/NIS otomatis menjadi username dan password awal. Tidak perlu kolom terpisah untuk username dan password.

4. **Template Excel:** Perlu dibuat manual (file binary). Kolom header harus sesuai dengan key di rules() (lowercase, underscore).

5. **Tidak ada perubahan schema** — tabel users sudah cukup.

6. **Role resolution:** Kolom `role` berisi nama role (admin/guru/siswa), bukan role_id. Import class resolve nama → role_id via tabel roles.

---

**Status:** 🟡 Draft - Menunggu Review & Approval  
**Prepared by:** Backend Developer + Kiro AI Assistant  
**Date:** 12 November 2026  
**Branch:** `docs/akun-bulk-upload-plan`