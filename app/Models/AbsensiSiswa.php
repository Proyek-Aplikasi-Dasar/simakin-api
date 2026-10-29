<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_id',
    'tanggal',
    'status',
    'latitude',
    'longitude',
    'foto_masuk',
    'jam_masuk',
    'keterangan',
    'status_submit',
    'dikoreksi_oleh',
])]
class AbsensiSiswa extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'absensi_siswa';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'tanggal',
        'status',
        'latitude',
        'longitude',
        'foto_masuk',
        'jam_masuk',
        'keterangan',
        'status_submit',
        'dikoreksi_oleh',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'jam_masuk' => 'string',
        ];
    }

    /**
     * Relasi ke Siswa (Many-to-One / BelongsTo).
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Relasi ke User korektor (Many-to-One / BelongsTo).
     */
    public function korektor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }
}
