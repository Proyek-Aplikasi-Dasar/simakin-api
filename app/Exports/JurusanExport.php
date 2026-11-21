<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JurusanExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Nama',
            'Kode',
            'Bidang Keahlian',
            'Status Aktif',
        ];
    }

    public function map($jurusan): array
    {
        return [
            ++$this->row,
            $jurusan->nama,
            $jurusan->kode,
            $jurusan->bidang_keahlian,
            $jurusan->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
