# Review Mockup Sprint 5-6 (Jurnal Harian & Penilaian Bulanan)

**Tanggal Review:** 11 November 2026  
**Task:** Sprint 0 Task 0.6 (Pahami User Flow)  
**Branch:** docs/sprint-0-update

---

## 1. Jurnal Harian (Mobile)

### Mockup yang Direview
- `docs/mockups/mobile/presensi-dan-jurnal/siswa-jurnal-harian.png` — Form input jurnal
- `docs/mockups/mobile/presensi-dan-jurnal/siswa-perbaiki-jurnal-harian.png` — Edit jurnal
- `docs/mockups/mobile/riwayat-dan-detail/siswa-riwayat-jurnal-new.png` — Riwayat jurnal (updated)
- `docs/mockups/mobile/riwayat-dan-detail/siswa-riwayat-jurnal-detail-new.png` — Detail jurnal (updated)

### Temuan dari Mockup
1. **Form input jurnal** (siswa-jurnal-harian.png):
   - Field: Tanggal, Kompetensi (dropdown), Deskripsi (textarea)
   - Tombol: "Simpan Jurnal"
   - **Tidak ada** indikator status review (pending/approved/need_revision)
   - **Tidak ada** field catatan guru

2. **Edit jurnal** (siswa-perbaiki-jurnal-harian.png):
   - Siswa bisa edit jurnal yang sudah disimpan
   - **Tidak ada** batasan "hanya bisa edit jika pending"
   - Konfirmasi: "Apakah Anda yakin ingin menyimpan perubahan?"

3. **Riwayat jurnal** (siswa-riwayat-jurnal-new.png):
   - List kronologis per tanggal
   - **Tidak ada** badge status (approved/pending/need_revision)
   - Tombol "Edit" tersedia di setiap item

4. **Detail jurnal** (siswa-riwayat-jurnal-detail-new.png):
   - Tampil: tanggal, kompetensi, deskripsi, foto
   - Tombol: "Edit" dan "Hapus"
   - **Tidak ada** tombol "Approve" atau "Revise"

### Kesimpulan Jurnal Harian
- ✅ Mockup sudah sesuai dengan requirement revisi (TANPA approval guru)
- ✅ Siswa bisa edit kapan saja (tidak ada lock)
- ✅ Guru hanya view (read-only)
- ✅ Admin bisa edit semua (tombol edit tersedia)

---

## 2. Penilaian Bulanan (Mobile)

### Mockup yang Direview
- `docs/mockups/mobile/penilaian-bulanan/siswa-form-isi-penilaian-bulanan.png` — Form input nilai
- `docs/mockups/mobile/penilaian-bulanan/siswa-foto-verifikasi-penilaian-bulanan.png` — Upload foto verifikasi
- `docs/mockups/mobile/penilaian-bulanan/siswa-verifikasi-penilaian-bulanan.png` — Verifikasi dengan token

### Temuan dari Mockup
1. **Form input nilai** (siswa-form-isi-penilaian-bulanan.png):
   - Siswa **tidak bisa** input nilai (hanya guru yang input)
   - Siswa hanya melihat nilai yang sudah diinput guru
   - Tampil: kompetensi + nilai, sikap + nilai, nilai akhir

2. **Verifikasi penilaian** (siswa-verifikasi-penilaian-bulanan.png):
   - Field: "Token Akses" (6 digit)
   - Info: nama perusahaan, nama siswa, periode penilaian
   - Tombol: "Verifikasi"

3. **Upload foto verifikasi** (siswa-foto-verifikasi-penilaian-bulanan.png):
   - Upload foto (swafoto dengan watermark GPS+time)
   - GPS otomatis dari device
   - Tombol: "Kirim Verifikasi"

### Kesimpulan Penilaian Bulanan
- ✅ Mockup sudah sesuai dengan requirement revisi (token 6 digit + foto + GPS)
- ✅ Siswa hanya baca nilai, verifikasi dengan token + foto
- ✅ Token 6 digit terlihat di UI (field "Token Akses")
- ✅ GPS otomatis dari device (tidak perlu input manual)

---

## 3. Jurnal Harian (Web Admin)

### Mockup yang Direview
- `docs/mockups/web/admin/monitoring/presensi-jurnal/jurnal-harian/admin-jurnal-new.png` — List jurnal
- `docs/mockups/web/admin/monitoring/presensi-jurnal/jurnal-harian/admin-detail-jurnal-new.png` — Detail jurnal
- `docs/mockups/web/admin/monitoring/presensi-jurnal/jurnal-harian/admin-detail-jurnal-edit-jurnal-new.png` — Edit jurnal
- `docs/mockups/web/admin/monitoring/presensi-jurnal/jurnal-harian/admin-detail-jurnal-tambah-jurnal-new.png` — Tambah jurnal
- `docs/mockups/web/admin/pengguna/siswa-pkl/admin-riwayat-jurnal-siswa-new.png` — Riwayat jurnal siswa

### Temuan dari Mockup
1. **List jurnal** (admin-jurnal-new.png):
   - Tabel: NIS, Nama, Tanggal, Kompetensi, Deskripsi (truncated)
   - Filter: tanggal range, siswa, kompetensi
   - **Tidak ada** kolom status review

2. **Detail jurnal** (admin-detail-jurnal-new.png):
   - Tampil: semua field jurnal
   - Tombol: "Edit" dan "Hapus"
   - **Tidak ada** tombol "Approve" atau "Revise"

3. **Edit jurnal** (admin-detail-jurnal-edit-jurnal-new.png):
   - Form edit semua field
   - Tombol: "Simpan Perubahan"
   - Set `edited_by` dan `edited_at` saat simpan

4. **Tambah jurnal** (admin-detail-jurnal-tambah-jurnal-new.png):
   - Form tambah jurnal untuk siswa
   - Admin bisa input jurnal atas nama siswa

5. **Riwayat jurnal siswa** (admin-riwayat-jurnal-siswa-new.png):
   - List kronologis
   - Tombol "Edit" di setiap item
   - **Tidak ada** badge status

### Kesimpulan Jurnal Harian (Web Admin)
- ✅ Mockup sudah sesuai dengan requirement revisi (TANPA approval)
- ✅ Admin bisa edit semua jurnal (tombol edit tersedia)
- ✅ Admin bisa tambah jurnal atas nama siswa
- ✅ Tidak ada tombol approve/revise

---

## 4. Penilaian Bulanan (Web Admin/Guru)

### Mockup yang Direview
- `docs/mockups/web/admin/monitoring/penilaian/admin-penilaian.png` — List penilaian
- `docs/mockups/web/admin/monitoring/penilaian/admin-penilaian-detail.png` — Detail penilaian
- `docs/mockups/web/guru/monitoring/penilaian-bulanan/guru-penilaian-bulanan.png` — Guru input nilai
- `docs/mockups/web/guru/monitoring/penilaian-bulanan/guru-penilaian-bulanan-detail-penilaian.png` — Detail penilaian guru

### Temuan dari Mockup
1. **Guru input nilai** (guru-penilaian-bulanan.png):
   - Form: siswa, aktivasi penilaian, kompetensi + nilai, sikap + nilai, catatan
   - Tombol: "Simpan Penilaian"
   - Setelah simpan → generate token 6 digit

2. **Detail penilaian** (guru-penilaian-bulanan-detail-penilaian.png):
   - Tampil: semua nilai, foto verifikasi, GPS, jarak, radius, token status
   - Status verifikasi: "Terverifikasi" / "Belum Terverifikasi"

3. **Admin list penilaian** (admin-penilaian.png):
   - Tabel: Siswa, Bulan, Nilai Akhir, Status Verifikasi
   - Filter: bulan, tahun, gelombang, jurusan

### Kesimpulan Penilaian Bulanan (Web)
- ✅ Mockup sudah sesuai dengan requirement revisi (token + GPS metadata)
- ✅ Guru input nilai → generate token
- ✅ Siswa verifikasi dengan token + foto
- ✅ Detail menampilkan jarak dan status radius

---

## 5. Presensi & Jurnal (Mobile - Lama vs Baru)

### Perbandingan Mockup Lama vs Baru

| Aspek | Mockup Lama (dengan approval) | Mockup Baru (-new) |
|---|---|---|
| Status review badge | ✅ Ada (Menunggu/Disetujui/Perlu Revisi) | ❌ Tidak ada |
| Tombol Approve/Revise | ✅ Ada (guru) | ❌ Tidak ada |
| Tombol Edit | Terbatas (hanya jika editable) | ✅ Selalu tersedia |
| Catatan guru | ✅ Ada | ❌ Tidak ada |
| Riwayat jurnal | Dengan status | Tanpa status |

### Kesimpulan
- Mockup baru (suffix `-new`) sudah sesuai dengan requirement revisi (TANPA approval)
- Mockup lama (tanpa suffix) masih menunjukkan flow lama (dengan approval)
- **Rekomendasi:** Hanya gunakan mockup `-new` untuk implementasi Sprint 5

---

## 6. Rekomendasi untuk Backend

Berdasarkan review mockup, berikut field/endpoint yang perlu disesuaikan:

### Jurnal Harian
1. ✅ Hapus field: `status_review`, `catatan_guru`, `reviewed_by`, `reviewed_at`, `is_editable`
2. ✅ Tambah field: `edited_by`, `edited_at`
3. ✅ Hapus endpoint: approve, revise
4. ✅ Tambah authorization: siswa owner + admin (CRUD), guru read-only
5. ✅ Rekap: hapus breakdown status, ganti total jurnal

### Penilaian Bulanan
1. ✅ Tambah field: `verifikasi_waktu`, `jarak_dari_pkl`, `dalam_radius`
2. ✅ Tambah field: `token_verifikasi`, `token_generated_at`, `token_used_at`
3. ✅ Tambah endpoint: `generate-token`
4. ✅ Update endpoint: `verifikasi` (validasi token + GPS + Haversine)
5. ✅ Tambah field di perusahaan: `radius_presensi`, `pembimbing_industri`, `bidang_usaha`

---

**Status:** ✅ Review Complete  
**Prepared by:** Backend Developer + Kiro AI Assistant  
**Date:** 11 November 2026