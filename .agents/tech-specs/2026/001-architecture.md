# Arsitektur Sistem SIMAKIN

## 1. High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      CLIENTS                            │
├──────────────────────┬──────────────────────────────────┤
│  Vue.js 3 SPA       │  Flutter Mobile App              │
│  (Admin & Guru)     │  (Siswa & Pembimbing Industri)   │
│  Web Browser        │  Android                         │
└──────────┬──────────┴──────────────┬────────────────────┘
           │ HTTPS (cookie/session)  │ HTTPS (Bearer token)
           │                         │
           ▼                         ▼
┌─────────────────────────────────────────────────────────┐
│                   WEB SERVER                            │
│            Nginx / Apache (reverse proxy)                │
│                                                         │
│   simakin.smkn1magelang.sch.id    api.simakin.smkn...   │
│   (Vue.js static files)          (Laravel API)          │
└──────────────────────────┬──────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                  LARAVEL 13 API                         │
│                                                         │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  Sanctum    │  │  Middleware   │  │  Controllers  │  │
│  │  (Auth)     │  │  (RBAC)      │  │  (Resources)  │  │
│  └─────────────┘  └──────────────┘  └───────────────┘  │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  Form       │  │  API         │  │  Services     │  │
│  │  Requests   │  │  Resources   │  │  (Business    │  │
│  │  (Validate) │  │  (Response)  │  │   Logic)      │  │
│  └─────────────┘  └──────────────┘  └───────────────┘  │
└──────┬──────────────────────┬───────────────┬───────────┘
       │                      │               │
       ▼                      ▼               ▼
┌────────────┐      ┌──────────────┐   ┌─────────────┐
│  MySQL 8+  │      │ Local Disk   │   │  Queue      │
│  (Database)│      │ (File Upload)│   │  (DB Notif) │
│            │      │              │   │             │
└────────────┘      └──────────────┘   └─────────────┘
```

## 2. API Design Principles

### 2.1 RESTful Conventions

| Aspect | Convention |
|---|---|
| Base URL | `{base_url}/api` |
| HTTP Methods | GET (read), POST (create), PUT (update), DELETE (remove) |
| Resource Naming | Kebab-case plural (`/api/lokasi-pkl`, `/api/jurnal-harian`) |
| Response Format | JSON (`Content-Type: application/json`) |
| Versioning | URL path jika diperlukan nanti (`/api/v2/...`) |
| Pagination | Laravel default (`links`, `meta`) dengan 20 item/halaman |
| Filtering | Query params (`?status=pending&siswa_id=1`) |
| Sorting | Query params (`?sort=created_at&order=desc`) |
| Search | Query params (`?search=nama`) |

### 2.2 Response Envelope

**Success (single resource):**
```json
{
  "data": { "id": 1, "nis": "001234", ... }
}
```

**Success (collection):**
```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 95 }
}
```

**Success (with message):**
```json
{
  "message": "Berhasil disimpan.",
  "data": { ... }
}
```

**Error (validation):**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Error (custom/business):**
```json
{
  "message": "Presensi hari ini sudah dilakukan.",
  "code": "ABSENSI_DUPLICATE"
}
```

### 2.3 Standard HTTP Status Codes

| Code | Usage |
|---|---|
| 200 | GET success, PUT success |
| 201 | POST success (resource created) |
| 204 | DELETE success (no content) |
| 401 | Unauthenticated (no token/session) |
| 403 | Forbidden (wrong role/ownership) |
| 404 | Resource not found |
| 422 | Validation error / business rule violation |
| 429 | Rate limit exceeded |
| 500 | Server error |

### 2.4 Custom Error Codes

| Code | HTTP | Description |
|---|---|---|
| `VALIDATION_ERROR` | 422 | Input validation failed |
| `UNAUTHORIZED` | 401 | Not authenticated |
| `FORBIDDEN` | 403 | Not authorized for this action |
| `NOT_FOUND` | 404 | Resource not found |
| `ABSENSI_DUPLICATE` | 422 | Already checked in today |
| `ABSENSI_REQUIRED` | 422 | Must check in before journal |
| `ABSENSI_EDIT_BLOCKED` | 422 | Cannot edit (already approved) |
| `JURNAL_NOT_EDITABLE` | 422 | Journal already approved by guru |
| `PENGAJUAN_DUPLICATE` | 422 | Already have active pengajuan |
| `PERUSAHAAN_KUOTA_PENUH` | 422 | Company quota full |
| `PERIODE_CLOSED` | 422 | Penilaian period not active |
| `GELOMBANG_NOT_ACTIVE` | 422 | Gelombang registration closed |
| `SISWA_ALREADY_PLACED` | 422 | Siswa already has active PKL |
| `NOT_SINGLE_GELOMBANG` | 422 | Siswa can only join 1 gelombang |
| `FILE_TOO_LARGE` | 422 | File exceeds size limit |
| `INVALID_FILE_TYPE` | 422 | File type not allowed |
| `SERVER_ERROR` | 500 | Internal server error |

## 3. Authentication Strategy

### 3.1 Multi-Platform Auth Flow

**Web (Admin & Guru) - Session-based:**
```
1. GET /sanctum/csrf-cookie        → Server set XSRF-TOKEN cookie
2. POST /api/login                 → Server set session cookie, return user
3. GET /api/user                   → Server read session, return user (no Authorization header)
4. POST /api/register              → Admin buat akun guru/admin baru (session)
5. PUT /api/user/password          → Ubah kata sandi (sesi lama lain dicabut)
6. POST /api/logout                → Server destroy session
```

- Frontend: Axios dengan `withCredentials: true`
- Header: `Origin: {FRONTEND_URL}`, `X-XSRF-TOKEN: {decoded cookie}`
- Tidak perlu `Authorization: Bearer`

**Mobile (Siswa) - Token-based:**
```
1. POST /api/login                 → Header X-Client: mobile → return user + token
2. GET /api/user                   → Header Authorization: Bearer {token}, X-Client: mobile
3. PUT /api/user/password          → Ubah kata sandi (token lain dicabut)
4. POST /api/logout                → Header Authorization: Bearer {token} → delete token
```

- Token disimpan di secure storage (Flutter: `flutter_secure_storage`)
- Token expiration: default Sanctum (sesuai config)
- Multiple devices: `createToken()` per device, `deleteToken()` saat logout
- Lupa kata sandi: hubungi admin (tidak ada alur reset via email)

### 3.2 Distinguishing Clients

Header `X-Client: mobile` menandakan request dari mobile app:
- **Ada** → return token (Sanctum personal access token)
- **Tidak ada** → return session-based auth (Sanctum SPA)

### 3.3 Role-Based Access Control (RBAC)

Middleware `role:admin,guru` (custom middleware di `app/Http/Middleware/`):

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('jurusan', JurusanController::class);
    // ...
});
```

Role IDs:
- `1` = admin
- `2` = guru
- `3` = siswa

### 3.4 Resource Ownership (Policy)

Siswa hanya bisa mengedit data miliknya sendiri:
```php
// app/Policies/JurnalPolicy.php
public function update(User $user, Jurnal $jurnal): bool
{
    return $jurnal->siswa->user_id === $user->id
        && $jurnal->status_review !== 'approved';
}
```

## 4. Anti-Double Submit Strategy

Berdasarkan pain point #2 (status submit tidak jelas, rawan submit ganda):

### 4.1 Database Level

**Unique constraint:**
- `absensi_siswa`: UNIQUE(`siswa_id`, `tanggal`)
- `jurnal_harian`: UNIQUE(`siswa_id`, `tanggal`)
- `pengajuan_pkl`: tidak perlu unique (bisa banyak, tapi ada status filter)

**Implementasi:**
```php
// Migration
$table->unique(['siswa_id', 'tanggal'], 'absensi_unique_siswa_tanggal');

// Controller - tangani duplicate gracefully
try {
    Absensi::create($data);
} catch (QueryException $e) {
    if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry
        return response()->json([
            'message' => 'Presensi hari ini sudah dilakukan.',
            'code' => 'ABSENSI_DUPLICATE',
        ], 422);
    }
    throw $e;
}
```

### 4.2 Idempotency Key (untuk retry)

Header `X-Idempotency-Key` (client-generated UUID):
```php
// Middleware atau di Controller
$idempotencyKey = $request->header('X-Idempotency-Key');
if ($idempotencyKey) {
    $cacheKey = "idempotency:{$idempotencyKey}";
    if (Cache::has($cacheKey)) {
        return response()->json([
            'message' => 'Request sudah diproses sebelumnya.',
            'data' => Cache::get($cacheKey),
        ], 200);
    }
    // Proses request...
    Cache::put($cacheKey, $response->getData(true), 3600);
}
```

### 4.3 Response Status Indicators

Setiap endpoint submit harus mengembalikan status yang jelas:
```json
{
  "success": true,
  "message": "Presensi berhasil disimpan.",
  "data": { "id": 1, "status": "hadir", "submitted_at": "..." },
  "next_action": "/jurnal/create"  // optional: redirect setelah presensi hadir
}
```

Client-side (Flutter/Vue):
- Tombol disabled selama loading
- Tampilkan status: loading → success/gagal
- Retry button jika gagal

## 5. File Upload Strategy

### 5.1 Storage Path Structure

```
storage/app/public/uploads/
├── presensi/{YYYY-MM-DD}/{user_id}_{timestamp}_{random}.{ext}
├── jurnal/{YYYY-MM-DD}/{user_id}_{timestamp}_{random}.{ext}
├── penilaian/{YYYY}/{bulan}/{user_id}_{timestamp}_{random}.{ext}
└── profile/{user_id}_profile.{ext}
```

### 5.2 Validation Rules

| Type | Max Size | Allowed MIME | Dimensions |
|---|---|---|---|
| Foto presensi | 5 MB | jpeg, jpg, png | - |
| Foto jurnal | 5 MB | jpeg, jpg, png | - |
| Foto verifikasi penilaian | 5 MB | jpeg, jpg, png | - |
| Dokumen Excel | 10 MB | xlsx, csv | - |

### 5.3 Watermark (Anti-Kecurangan Penilaian)

Watermark GPS + timestamp dilakukan di **mobile app (Flutter)** sebelum upload:
- Overlay teks: `{koordinat GPS}, {tanggal waktu}` pada foto
- Backend: validasi metadata EXIF jika ada, simpan `verifikasi_lat`, `verifikasi_long`, `verified_at` di database

### 5.4 Public URL

```php
// Simpan path relatif di database
'foto_masuk' => 'uploads/presensi/2026-10-15/4_1728960000_abc123.jpg'

// Generate URL publik
Storage::url($absensi->foto_masuk); // => /storage/uploads/presensi/...
```

## 6. Retry Mechanism

Berdasarkan pain point #4 (presensi gagal saat jaringan lemah):

### 6.1 Backend Support

- **Idempotency key** mencegah duplikasi saat retry
- **Clear error codes** agar client tahu apa yang harus dilakukan
- **Partial save**: foto upload terpisah dari data presensi (endpoint `POST /api/absensi/upload-foto`) sehingga jika foto gagal, data sudah tersimpan

### 6.2 Client Guidance (Flutter)

```
Retry policy:
- Max attempts: 3
- Delay: exponential backoff (1s, 2s, 4s)
- Timeout: 30s per attempt
- Offline queue: simpan di local DB, sync saat online
```

## 7. Notification Strategy (PAD 1)

### 7.1 Scope PAD 1

| Type | Medium | Trigger |
|---|---|---|
| Database notification | `notifications` table | Jurnal di-review, pengajuan disetujui, penilaian diinput |

> PAD 1 hanya memakai **database notification** (tanpa email). Reset kata sandi tidak melalui email — pengguna yang lupa sandi menghubungi admin.

### 7.2 Out of Scope PAD 1 (PAD 2)

- Push notification mobile (FCM)
- Real-time WebSocket notification
- SMS notification

### 7.3 Notification Triggers

| Event | Recipients | Type |
|---|---|---|
| Jurnal direview (approve/revise) | Siswa | Database |
| Pengajuan PKL disetujui/ditolak | Siswa | Database |
| Penilaian bulanan diinput | Guru pembimbing | Database |
| Verifikasi penilaian | Siswa | Database |
| Presensi dikoreksi admin | Siswa | Database |

## 8. Performance & Scalability

### 8.1 Database Optimization

**Indexing:**
```php
// Foreign keys (default Laravel: indexed)
$table->foreignId('siswa_id')->constrained();

// Composite indexes untuk query umum
$table->index(['siswa_id', 'tanggal']);        // absensi per siswa per hari
$table->index(['siswa_id', 'tanggal']);        // jurnal per siswa per hari
$table->index(['siswa_id', 'bulan', 'tahun']); // penilaian per siswa per bulan
$table->index('status');                        // filter status
$table->index('created_at');                    // sorting

// Search fields
$table->unique('nis');          // siswa
$table->unique('nip');          // guru (jika NIP unik)
$table->unique('email');        // users
```

**Query Optimization:**
```php
// Eager loading (hindari N+1)
LokasiPkl::with(['siswa.user', 'guru.user', 'perusahaan'])->get();

// Select hanya kolom yang dibutuhkan
Siswa::select('id', 'nis', 'user_id')->get();

// Pagination (bukan all())
Siswa::paginate(20);

// Chunking untuk bulk operations
Siswa::chunk(100, function ($siswa) { ... });
```

### 8.2 Caching

```php
// Dashboard stats (cache 10 menit)
$stats = Cache::remember('dashboard.stats', 600, function () {
    return [
        'total_siswa' => Siswa::count(),
        'total_guru' => Guru::count(),
        'total_perusahaan' => Perusahaan::count(),
        'siswa_active_pkl' => LokasiPkl::where('status', 'active')->count(),
    ];
});
```

### 8.3 Queue

Queue dipakai untuk proses berat (mis. bulk upload Excel, pengiriman notifikasi database massal). PAD 1 tidak mengirim email, sehingga `MAIL_*` tidak wajib dikonfigurasi:

```php
// Contoh notifikasi database
class JurnalDireview extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }
}

// Kirim via queue
$user->notify((new JurnalDireview($jurnal))->onQueue('notifications'));
```

Queue driver: `database` (development), `redis` (production recommended).

## 9. Security

| Aspect | Implementation |
|---|---|
| HTTPS | Wajib di production (reverse proxy termination) |
| Rate Limiting | 60 req/min (authenticated), 120 req/min (guest) |
| CORS | `config/cors.php`: paths `api/*`, `sanctum/csrf-cookie` |
| CSRF | Sanctum CSRF cookie untuk web session |
| SQL Injection | Eloquent ORM (parameterized queries) |
| XSS | API JSON response (auto-escaped), Vue.js auto-escape |
| Password | Bcrypt (Laravel default, 12 rounds) |
| File Upload | Validate MIME type + size, random filename |
| Mass Assignment | `$fillable` di setiap model |
| Soft Delete | Untuk data penting (siswa, guru, perusahaan) |
| API Token | Sanctum personal access token (mobile) |

## 10. Deployment Architecture

### 10.1 Environment Separation

| Environment | URL | Purpose |
|---|---|---|
| Development | `localhost:8000/api` | Local development (Laragon) |
| API Production | `api.simakin.smkn1magelang.sch.id` | Laravel API |
| Web Production | `simakin.smkn1magelang.sch.id` | Vue.js SPA |
| Mobile Production | Play Store / APK | Flutter app |

### 10.2 CORS Configuration

Karena API dan Web beda domain:
```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [
    'https://simakin.smkn1magelang.sch.id',
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### 10.3 Production Setup Checklist

```bash
# 1. Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.simakin.smkn1magelang.sch.id

# 2. Database
DB_HOST=production-db-host
DB_DATABASE=simakin_production

# 3. Session & Sanctum
SANCTUM_STATEFUL_DOMAINS=simakin.smkn1magelang.sch.id
SESSION_DRIVER=database
SESSION_DOMAIN=simakin.smkn1magelang.sch.id

# 4. Frontend URL
FRONTEND_URL=https://simakin.smkn1magelang.sch.id

# 5. Mail (opsional untuk PAD 1 — tidak ada notifikasi email)
MAIL_MAILER=log

# 6. Cache & Queue (Redis recommended for production)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

```bash
# Post-deploy
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 11. Technology Decisions (Team Decisions)

### 11.1 Controller Pattern

| Use Case | Pattern | Alasan |
|---|---|---|
| Simple CRUD (Jurusan, Kompetensi, Sikap, Waktu Absen) | Resource Controller | Standard, less code |
| Complex logic (Absensi, Jurnal, Penilaian) | Custom Controller | Custom methods (checkin, review, rekap) |

**Status:** Belum ada kesepakatan resmi tim. Implementasi mengikuti use case per modul.

### 11.2 Data Access Pattern

| Phase | Pattern | Alasan |
|---|---|---|
| PAD 1 | Direct Eloquent di Controller | Speed, simplicity |
| PAD 2 (opsional) | Repository Pattern | Refactor jika logic kompleks bertambah |

**Status:** Belum ada kesepakatan resmi tim. PAD 1 pakai direct Eloquent, refactor ke Repository jika diperlukan.
