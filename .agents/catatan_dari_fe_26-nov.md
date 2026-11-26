# **Catatan dari FE Web - 26 November 2026**

## **Mekanisme Ekspor**
Mekanisme ekspor data dalam format Excel untuk:
1. Siswa
2. Guru
3. Perusahaan
4. Jurusan
5. Kompetensi
6. Gelombang PKL
7. Sikap
8. Lokasi dan Penempatan PKL
9. Pengajuan PKL
10. Presensi Siswa
11. Jurnal Harian
12. Rekap Presensi & Jurnal

Pastikan semua fitur data tersebut sudah memiliki mekanisme ekspor.

## **Validasi**
1. Sekarang gelombang bisa aktif banyak sekaligus, padahal harusnya cuma boleh 1. Tolong dibikin supaya kalau ada gelombang yang diaktifin, gelombang aktif yang lain otomatis dimatiin. Paling gampang taruh di model Gelombang pakai event saving, jadi store, update, sama import ikut kena semua.
2. Data gelombang yang udah ada kayaknya banyak yang is_active = true. Tolong dirapiin sekali jalan (migration/seeder), sisain 1 aja yang aktif, misalnya yang periode prakteknya lagi jalan.

Opsional: bikin unique index biar nggak bisa ada 2 gelombang aktif walau dua admin save barengan, dan bikin import nolak file yang isinya lebih dari 1 gelombang aktif.

