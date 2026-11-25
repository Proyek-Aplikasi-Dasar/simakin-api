# **Catatan Analisis UAT Back-End dari UI/UX**

## **Perlu diperbaiki:**
1. Import Excel selalu ditolak (Siswa, Guru, Perusahaan, Kompetensi, Akun). Controller harus simpan file temp dengan ekstensi .xlsx sebelum dikirim ke importer.
2. Sandi akun hasil import masih `password123`, harus diganti jadi `NIS`. Ini sekaligus menutup item "Sandi awal siswa nggak otomatis NIS".
3. Kuota tidak dicek saat approve (`PengajuanPklController@approve`). Tambah validasi: kalau kuota perusahaan sudah penuh, tolak approve.
4. Guru bisa lihat presensi siswa non-bimbingan (`AbsensiSiswaController`). Filter query berdasarkan relasi guru pembimbing.


## **Perlu dicek (asumsi):**
1. Filter bidang usaha perusahaan: kalau mau ditambah, endpoint list perusahaan harus terima parameter bidang_usaha (dan kolom/relasinya harus ada di data).
2. ID pengajuan tidak ada: backend sebaiknya balikin 404 dengan pesan jelas, bukan error umum. Cek dulu responsnya sekarang.
3. Default verifikasi "Diterima": ini sumber masalah item Canceled. Kalau mau bisa dites, default-nya dihapus dan keputusan jadi wajib diisi (validasi required).
