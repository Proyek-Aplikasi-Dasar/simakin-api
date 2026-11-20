# Catatan dari FE Web

## ADMIN

1. **Halaman Akun & Peran:** Kolom login terakhir (contoh: `"Hari ini, 07:12"`) dan status (contoh: `"Aktif"`, `"Nonaktif"`) belum ada.
2. **Halaman Guru Pembimbing:** Email di detail guru ada di UI, tetapi di BE tidak ada.
3. **Halaman Pengajuan PKL:** Field `"Dipilih Dari"` (di desain: `"Daftar perusahaan mitra"`) belum tersedia di BE.
   * Yang dibutuhkan adalah satu field di data pengajuan PKL yang menyimpan sumber atau asal pilihan perusahaan oleh siswa. Saat ini tabel `pengajuan_pkl` hanya memiliki kolom: `siswa_id`, `perusahaan_id`, `gelombang_id`, `status`, `alasan_penolakan`, `catatan`, `verified_by`, `verified_at`, dan `timestamp`.
   * `PengajuanPklResource` juga tidak mengirimkan field tersebut. Jadi, endpoint `GET /api/pengajuan-pkl/{id}` tidak memiliki nilai untuk `"Dipilih dari"`.
   * Pada mockup `docs/mockups/web/admin/pkl/pengajuan-pkl/admin-pengajuan-pkl-verifikasi-new.png`, terdapat 2 field tambahan di UI, yaitu **"Tanggal Pengajuan"** (bernilai tanggal dan waktu dilakukannya pengajuan) dan **"Dipilih dari"** (bernilai `"Daftar perusahaan siswa"`).
4. **Halaman Lokasi & Penempatan:** Pembimbing Industri dan Koordinat di form: endpoint penempatan belum memiliki field ini (nilainya hanya tampil dari data perusahaan).
   * Untuk **"form tambah penempatan"**, dibutuhkan endpoint untuk Pembimbing Industri dan Koordinat.
5. **Halaman Presensi Siswa:** Kurang informasi waktu kirim presensi & jam shift.
6. **Masalah Impor Excel:**
   * **Pesan dari FE:**
     > Halo, mau lapor bug di BE: semua import Excel (`bulk-upload`) selalu ditolak walaupun file `.xlsx`-nya valid.
     >
     > **Cara memunculkan bug:**
     > 1. Login admin, buka halaman **Export/Import** di FE.
     > 2. Pilih **Jenis Data** (misal: Siswa), pilih file `template-siswa.xlsx` hasil unduh dari template BE.
     > 3. Klik **Mulai Import**.
     >
     > **Hasilnya:** BE membalas status `422` dengan pesan: `"Format file "phpEB6E.tmp" tidak valid. Unggah file Excel (.xlsx) atau CSV."` (kode: `INVALID_FILE_TYPE`).
     >
     > **Penyebab (dari membaca kode, belum di-debug langsung):**
     > Controller `bulkUpload` memanggil `$request->file('file')->getRealPath()`, yang berisi path file sementara PHP (`phpXXXX.tmp`). Lalu `ImportsRows::readRows()` di `app/Imports/Concerns/ImportsRows.php` mengecek ekstensi dari path sementara tersebut, sehingga `"tmp"` dianggap bukan `xlsx`/`xls`/`csv` dan langsung melemparkan `InvalidFileTypeException`. Request dari FE sendiri sudah benar (`multipart`, field `"file"`), dan validasi `mimes:xlsx,xls,csv` di controller sudah lolos.
     >
     > **Usulan perbaikan:**
     > Jangan cek ekstensi dari path sementara. Misalnya, tentukan tipe reader secara eksplisit:
     > ```php
     > Excel::toArray($this, $path, null, \Maatwebsite\Excel\Excel::XLSX);
     > ```
     > Atau cek ekstensi dari `getClientOriginalExtension()` lalu kirim tipe readernya ke import. Karena `ImportsRows` dipakai bersama, perbaikan di bagian tersebut akan sekaligus berlaku untuk import Siswa, Guru, Perusahaan, Kompetensi, dan Akun.
     >
     > Tolong dicek ya. Kalau sudah diperbaiki kabari, nanti aku coba import lagi dari FE.

---

# Catatan dari UI/UX

1. Fitur token untuk penilaian dihapus. Cukup admin yang membuka akses penilaian pada tanggal **1–10 tiap bulannya**.

---

# Catatan dari BE

1. Validasi email, nomor telepon, dan seluruh field untuk input/edit data.
2. Waktu kirim saat presensi masih berformat **GMT**, perlu disesuaikan menjadi **WIB (Waktu Indonesia Barat / GMT+7)**.