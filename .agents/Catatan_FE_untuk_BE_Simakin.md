# Catatan dari Front-End untuk Back-End: Proyek Aplikasi Dasar 1 (Simakin)

# Prompt dari FE:
Halo aku kembali lagi, seorang FE development yang sedang merancang projek aplikasi simakin. tolong bantu aku cek ketidaksesuaian tabel  yang ada di BE dengan kolom yang ada di UI. Ini link UInya https://www.figma.com/design/LxOC7gOQS8smK9hFFNQD4z/Untitled?node-id=0-1&t=vfbCFcFAOyvxE96l-1 . Catat yang sudah ada di BE tetapi tidak sinkron dengan UI

# Respon dari Claude:
Hasil perbandingan 23 migration BE (`simakin-api-develop`) dengan frame "WEB — ADMIN" di Figma. Istilah UI "Program Studi" ditulis sebagai `jurusan`, sesuai aturan yang diminta.

---

## A. Ada di BE, tapi tidak sinkron dengan UI

| # | Tabel.kolom BE | Kondisi di UI | Dampak untuk FE |
|---|----------------|---------------|-----------------|
| 1 | `gelombang.daftar_mulai` dan `daftar_selesai` (wajib diisi) | Form hanya punya satu pasang tanggal (Mulai dan Selesai). Di list tampil sebagai "PERIODE 6 Jul – 18 Des", jadi kemungkinan itu `praktik_*` | `POST /gelombang` akan kena 422 kalau FE cuma kirim 2 tanggal |
| 2 | `perusahaan.pimpinan` | Tidak ada field "Pimpinan". Yang ada "Pembimbing Industri", yang maknanya beda | Jangan dipetakan paksa ke `pimpinan` |
| 3 | `perusahaan.latitude` dan `longitude` (2 kolom) | Satu field "Koordinat" | FE perlu memecah atau menggabung sendiri |
| 4 | `users.username` | Tidak ada field username. Field "NIP / NIS" otomatis jadi username dan password awal | Aturan BE: username harus sama dengan `nip` atau `nis`, dan password wajib dikirim beserta `password_confirmation`. Padahal UI tidak punya field password saat tambah, jadi FE harus mengisinya dengan NIP/NIS |
| 5 | `users.deleted_at` dan `siswa.deleted_at` (soft delete) | UI punya toggle "Akun aktif / nonaktifkan untuk blokir login" | Soft delete artinya dihapus, bukan dinonaktifkan |
| 6 | `users.email_verified_at`, `remember_token` | Tidak dipakai UI | Aman diabaikan |
| 7 | `waktu_absen` (mulai, selesai, keterangan) beserta CRUD-nya | Tidak ada halaman untuknya. Di Pengaturan Sistem tertulis "Jam shift diisi siswa" (rentang bebas, mis. 07:00–15:00) | Konsepnya bentrok dengan UI |
| 8 | `absensi_siswa.status_submit` (success/failed) | Tidak ditampilkan | Tidak dipakai UI |
| 9 | `absensi_siswa.dikoreksi_oleh` (hanya id user) | Edit Presensi menampilkan "Data Sebelumnya" dan "Diubah oleh" | BE tidak punya riwayat perubahan |
| 10 | `pengajuan_pkl.alasan_penolakan` (wajib hanya saat reject) | Kolom "Catatan untuk Siswa" juga dipakai saat terima ("Pengajuan diterima. Mulai presensi…") | Endpoint approve hanya menerima `guru_pembimbing_id`, jadi catatan saat terima tidak punya tempat |
| 11 | `lokasi_pkl.status` (pending/active/completed/cancelled) dan `catatan` | Di frame yang dibaca, status yang terlihat hanya "Aktif PKL" atau "—" | 4 status BE belum terpetakan ke UI |
| 12 | `lokasi_pkl.gelombang_id` (wajib) | Form Tambah Penempatan tidak punya field gelombang | FE harus mengambilnya dari `siswa.gelombang_id` |
| 13 | `jurusan.kode`, `kompetensi.kode` (nullable, tanpa unique) | UI menulis "Pastikan kode unik" dan kolom Kode jadi yang pertama | Validasi unik tidak dijaga BE |
| 14 | `siswa.nis` (satu kolom) | Form menulis "NIS / NISN" dan detail menampilkan dua angka (20210041 / 0061234567) | NISN tidak punya kolom |
| 15 | `siswa.jenis_kelamin` (L/P) | Label "Laki-laki" / "Perempuan" | FE perlu mapping |
| 16 | `siswa.kelas` (string bebas) | Dropdown "Pilih kelas" | BE tidak punya master kelas, jadi sumber opsinya belum jelas |

### Penamaan yang beda tapi sudah nyambung

- `sikap` di BE sama dengan "Soft Skill" di UI.
- `lokasi_pkl` dan `perusahaan` sama dengan "Lokasi & Penempatan".
- `profil_confirmed_at` sama dengan "Status Data: Dikonfirmasi".
- `roles` sama dengan 3 peran di UI.
- `notifications` sama dengan halaman Notifikasi.

`sessions`, `cache`, `jobs`, dan `personal_access_tokens` memang infrastruktur, tidak butuh UI.

---

## B. Kebalikannya: ada di UI, belum ada di BE

Ini penting karena field-nya tidak bisa disimpan sampai BE menambah kolom.

- **Status aktif/nonaktif** muncul di Jurusan, Kompetensi, Soft Skill, Gelombang (hanya satu yang aktif), Perusahaan, Guru, dan Akun. Tidak ada satu pun kolom `is_active` di BE. Ini gap terbesar.
- **Jurusan:** `bidang_keahlian`, serta hitungan SISWA dan KOMPETENSI (BE tidak mengembalikan count).
- **Kompetensi:** "Dipakai di Jurnal" butuh tabel jurnal, dan tabelnya belum ada.
- **Perusahaan:** `bidang_usaha`, `radius_presensi`, `pembimbing_industri`, dan daftar "Bidang Pekerjaan".
- **Siswa:** tempat dan tanggal lahir, NISN, serta opsi Tahun Ajaran (BE hanya punya `gelombang.tahun_ajaran`).
- **Guru:** telp, jurusan, kuota bimbingan, dan alamat. Tabel guru hanya punya `user_id` dan `nip`.
- **Akun:** nomor telepon, "Login Terakhir", "Wajib ganti password", dan 6 checkbox hak akses (tidak ada tabel permission).
- **Penempatan:** tanggal mulai/selesai dan pembimbing industri, tidak ada di `lokasi_pkl`.
- **Presensi:** jam shift mulai/selesai (tidak ada kolom di `absensi_siswa`) dan berkas pendukung izin (contoh di UI: `surat-izin-lks-kota.pdf`).
- **Modul tanpa tabel sama sekali:** Jurnal Harian, Penilaian Bulanan, Nilai Akhir, Chat, dan pengaturan tutup bulan di Pengaturan Sistem. Migration BE baru sampai `absensi_siswa`.

---

## Yang sebaiknya dibawa ke diskusi dengan Angga (Back-End) dan UI/UX

1. Tambah `is_active` atau `status` di tabel-tabel master (poin B pertama).
2. Putuskan nasib `daftar_*` di `gelombang`: dihapus dari BE atau ditambah ke form UI.
3. Putuskan `pimpinan` atau "Pembimbing Industri", dan perlu tidaknya kolom `radius_presensi`.
4. Putuskan konsep jam shift: master `waktu_absen` atau diisi siswa.
5. Putuskan catatan saat approve pengajuan.

---

## Batasan

Figma tidak punya spec field, jadi beberapa pemetaan di atas (misalnya periode jadi `praktik_*`) adalah tafsiran. Frame "WEB — GURU" belum dibandingkan, dan Edit Siswa dianggap sama dengan Tambah Siswa karena teksnya tidak terbaca utuh.
