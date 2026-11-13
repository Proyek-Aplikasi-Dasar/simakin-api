<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AbsensiExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $row = 0;

    public function __construct(private $items) {}

    public function collection(): Collection
    {
        return $this->items instanceof Collection
            ? $this->items
            : collect($this->items);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Jurusan',
            'Perusahaan PKL',
            'Status',
            'Waktu Kirim (Jam Masuk)',
            'Jam Shift',
            'Keterangan',
            'Dikoreksi Oleh',
        ];
    }

    public function map($absensi): array
    {
        $siswa = $absensi->siswa;
        $perusahaan = $siswa?->lokasiPkl?->where('status', 'active')->first()?->perusahaan?->nama;

        $jamShift = trim(($absensi->jam_shift_mulai ?? '').' - '.($absensi->jam_shift_selesai ?? ''), ' -');

        return [
            ++$this->row,
            $absensi->tanggal?->format('Y-m-d'),
            $siswa?->nis,
            $siswa?->user?->name,
            $siswa?->kelas,
            $siswa?->jurusan?->nama,
            $perusahaan,
            $absensi->status,
            $absensi->jam_masuk,
            $jamShift ?: null,
            $absensi->keterangan,
            $absensi->korektor?->name,
        ];
    }
}
