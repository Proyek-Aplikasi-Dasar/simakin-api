# PRD - SIMAKIN (Sistem Informasi Manajemen Aktivitas & Kegiatan Industri PKL)

## 1. Overview

| Item | Nilai |
|---|---|
| Nama Produk | SIMAKIN |
| Institusi | SMK Negeri 1 Magelang |
| Kelompok | 5 mahasiswa (PM, UI/UX, FE Web, FE Mobile, Back-End) |
| Mata Kuliah | Proyek Aplikasi Dasar 1 (PAD 1) |
| Target Demo | 12 Desember 2026 |
| Total Pengerjaan | 2 semester (PAD 1 + PAD 2) |

SIMAKIN adalah sistem informasi terintegrasi untuk mengelola program Praktik Kerja Lapangan (PKL) di SMK Negeri 1 Magelang. Sistem ini menyatukan presensi, jurnal harian, penilaian, monitoring, dan rekap PKL dalam satu platform.

## 2. Problem Statement

Sistem PKL sebelumnya (aplikasi + website) mengalami masalah:

- **Server kehabisan kapasitas** sehingga presensi dan pengisian jurnal harian siswa terganggu, khususnya pada jam masuk/pulang saat pengguna mengakses bersamaan.
- **Developer lama sulit dihubungi** untuk perbaikan sehingga memaksa sekolah kembali ke proses manual.
- **Kanal darurat terpecah-pecah**: Google Form, buku manual, chat pribadi ke guru.
- **Kecurangan penilaian bulanan** terbukti terjadi: siswa mengisi nilai sendiri.
- **Rekap otomatis (kehadiran & jurnal) tidak bisa dilakukan** pada sistem lama.
- **Tidak ada notifikasi/pengingat** otomatis presensi & jurnal harian.
- **Guru sulit memantau** siswa bimbingan: harus klik berulang kali & berpindah halaman.

## 3. Target Users

| Role | Jumlah | Akses |
|---|---|---|
| Siswa PKL | +700 siswa/angkatan | Mobile (Flutter) |
| Admin sekolah | 1-3 orang | Web (Vue.js) |
| Guru pembimbing | Sesuai penugasan | Web (Vue.js) |
| Pembimbing industri | Banyak (via akun siswa) | Mobile (Flutter) |

## 4. Value Proposition

> Kami membantu SMK Negeri 1 Magelang mengelola program PKL bagi ratusan siswa dengan mudah, dengan menyediakan sistem informasi terintegrasi untuk presensi, jurnal harian, penilaian, dan monitoring PKL secara real-time dan terpusat.

**Pembeda utama** (dibanding sistem lama):
- Status pengiriman yang jelas (tidak lagi bikin bingung apakah presensi berhasil)
- Kemampuan edit/batal jika salah input
- Notifikasi otomatis (dulu siswa harus pasang alarm sendiri)
- Verifikasi nilai bulanan yang lebih ketat (mengatasi kecurangan)
- Kapasitas server direncanakan sejak awal berdasarkan estimasi pengguna aktif

## 5. Scope MVP (PAD 1 + PAD 2)

### Scope PAD 1 (Deadline: 12 Desember 2026)

Sesuai `docs/to-do-backend-pad-1.txt`:

1. **Manajemen Data Master** - Siswa, industri, guru, kompetensi + bulk upload Excel
2. **Pengajuan & ACC Lokasi PKL** - Pendaftaran siswa, approval sekolah, kuota industri
3. **Presensi Harian** - Swafoto + koordinat GPS, status jelas, edit/batal jika salah
4. **Jurnal Harian** - Terintegrasi kompetensi kurikulum, review guru
5. **Penilaian Bulanan** - Input nilai kompetensi + sikap, verifikasi (anti-kecurangan)
6. **Monitoring Guru** - Dashboard ringkasan siswa bimbingan, review jurnal
7. **Role & Akses** - RBAC admin/guru/siswa
8. **Autentikasi Lengkap** - Login multi-platform, registrasi (admin), ubah kata sandi
9. **Rekap Excel** (basic) - Export data presensi/jurnal/penilaian

### Scope PAD 2 (Lanjutan Semester Depan)

- Chat guru-siswa (real-time)
- Push notification mobile (FCM)
- Dashboard admin lanjutan (chart, statistik kompleks)
- Rekap Excel lanjutan (format kustom)
- Fitur lain sesuai feedback sekolah

### Out of Scope PAD 1

- Chat pembimbing
- Push notification mobile
- Validasi radius GPS presensi (cukup rekam lat/long)
- Preview screen mockup yang tidak terkait backend

## 6. Key Pain Points & Solutions (dari MVP)

| # | Pain Point | Solution (PAD 1) |
|---|---|---|
| 1 | Presensi/jurnal gagal saat traffic tinggi | Kapasitas server direncanakan, queue, optimization |
| 2 | Status submit tidak jelas, rawan double submit | Indikator status jelas (berhasil/gagal/coba lagi), unique constraint, idempotency key |
| 3 | Tidak bisa edit/batal jika salah input | Endpoint update/delete untuk status tertentu |
| 4 | Presensi gagal saat jaringan lemah, tanpa retry | Mekanisme retry otomatis & anti-double-submit |
| 5 | GPS lambat/gagal tanpa penjelasan | Status deteksi lokasi jelas (mencari/berhasil/gagal), fallback |
| 6 | Setelah presensi hadir, harus navigasi manual ke jurnal | API presensi sukses → response redirect ke jurnal |
| 7 | Batas karakter jurnal terlalu sedikit | Field deskripsi `text` (unlimited) |
| 8 | Pilihan kompetensi tidak cukup | CRUD kompetensi admin, banyak pilihan per jurusan |
| 9 | Input data 700 siswa satu per satu | Bulk upload Excel |
| 10 | ACC lokasi PKL satu per satu | Bulk approve pengajuan |
| 11 | Kecurangan penilaian bulanan | Watermark GPS+waktu, notifikasi ke guru saat nilai diinput |
| 12 | Rekap otomatis belum ada | Rekap Excel endpoint |
| 13 | Monitoring guru susah, harus pindah halaman | Dashboard guru: sekali pandang status siswa |
| 14 | Belum ada reminder presensi/jurnal | Notifikasi database |
| 15 | Riwayat jurnal tidak urut kronologis | Endpoint riwayat terurut per tanggal |

## 7. User Roles & Permissions Matrix

| Feature | Admin | Guru | Siswa |
|---|---|---|---|
| Login | Web | Web | Mobile |
| Register user | CRUD (guru/admin) | - | - (admin only create) |
| Kelola data master | Full CRUD | Read only | - |
| Bulk upload Excel | Full | - | - |
| Pengajuan PKL | Approve/Reject | Approve/Reject | Create/Cancel |
| Penempatan (lokasi PKL) | Full CRUD + bulk approve | Read (siswa bimbingan) | Read (milik sendiri) |
| Presensi | Koreksi manual | View + Koreksi | Create/Update/Delete |
| Jurnal harian | View semua | Review (approve/revise) | CRUD (milik sendiri) |
| Penilaian bulanan | View semua | Input/Edit nilai | Verifikasi (foto) |
| Dashboard | Stats keseluruhan | Siswa bimbingan | Status diri sendiri |
| Notifikasi | Read/Write | Read/Write | Read/Write |
| Rekap Excel | Download | Download | - |

## 8. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Database | MySQL 8.0+ |
| Authentication | Laravel Sanctum (session web + token mobile) |
| Frontend Web | Vue.js 3 (repository terpisah) |
| Frontend Mobile | Flutter (repository terpisah) |
| File Storage | Local disk (development), web server disk (production) |
| Queue | Database driver (development) |
| API Documentation | Markdown (`docs/API-Docs-fondasi.md`) + Postman Collection |
| Environment | Laragon (development) |
| Deployment | Hosting terpisah (API Laravel & Web Vue beda domain/subdomain) |

## 9. Success Criteria

### PAD 1 (12 Desember 2026)

- [ ] Autentikasi lengkap: login, logout, register (admin), ubah kata sandi (web + mobile)
- [ ] RBAC berjalan: admin/guru/siswa dengan akses berbeda
- [ ] CRUD data master: siswa, guru, perusahaan, jurusan, kompetensi, gelombang, sikap + bulk upload Excel
- [ ] Pengajuan PKL: siswa apply → admin/guru approve/reject → penempatan
- [ ] Presensi: check-in dengan foto + GPS, status jelas, edit/batal, koreksi admin
- [ ] Jurnal harian: input terintegrasi kompetensi, review guru (approve/revise)
- [ ] Penilaian bulanan: input nilai, verifikasi foto + watermark, notifikasi ke guru
- [ ] Dashboard guru: ringkasan siswa bimbingan (sudah/belum absen/lapor)
- [ ] Rekap Excel: export presensi/jurnal/penilaian
- [ ] Postman collection lengkap untuk semua endpoint
- [ ] Integration test untuk semua endpoint kritis
- [ ] Dokumentasi API lengkap

### PAD 2 (Semester Depan)

- [ ] Chat guru-siswa real-time
- [ ] Push notification mobile
- [ ] Feedback sekolah diintegrasikan
- [ ] Production deployment

## 10. Constraints & Assumptions

| Item | Detail |
|---|---|
| Timeline | 9 minggu (9 Okt - 12 Des 2026) untuk PAD 1 |
| Backend team | 1 orang (Back-End) + bantuan AI |
| Concurrent users | Estimasi 700+ siswa aktif di jam masuk/pulang |
| Foto presensi | ~700 siswa x 100 hari x 1 foto = ~70.000 foto (perlu perencanaan storage) |
| Database | Fresh install (belum ada data produksi) |
| Hosting | Terpisah: API Laravel & Web Vue beda domain/subdomain |
| Chat | Tidak masuk scope PAD 1 |
| GPS validation | Rekam lat/long tanpa validasi radius |
| Login identifier | `username` (unik): siswa = NIS, guru = NIP, admin = kustom. Email tidak dipakai di PAD 1 (notifikasi database saja); lupa sandi via admin |
| Excel | Bulk upload (siswa, guru, perusahaan) + export rekap |
