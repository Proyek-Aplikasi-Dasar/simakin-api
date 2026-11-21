<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GelombangExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            'Tahun Ajaran',
            'Nama',
            'Daftar Mulai',
            'Daftar Selesai',
            'Praktik Mulai',
            'Praktik Selesai',
        ];
    }

    public function map($gelombang): array
    {
        return [
            ++$this->row,
            $gelombang->tahun_ajaran,
            $gelombang->nama,
            $gelombang->daftar_mulai?->format('Y-m-d'),
            $gelombang->daftar_selesai?->format('Y-m-d'),
            $gelombang->praktik_mulai?->format('Y-m-d'),
            $gelombang->praktik_selesai?->format('Y-m-d'),
        ];
    }
}
