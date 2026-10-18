<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'nama',
    'alamat',
    'latitude',
    'longitude',
    'pimpinan',
    'telp',
    'email',
    'deskripsi',
    'kuota',
])]
class Perusahaan extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'perusahaan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'pimpinan',
        'telp',
        'email',
        'deskripsi',
        'kuota',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'kuota' => 'integer',
        ];
    }

    /**
     * Relasi ke Jurusan (Many-to-Many / BelongsToMany) via pivot perusahaan_jurusan.
     */
    public function jurusan(): BelongsToMany
    {
        return $this->belongsToMany(Jurusan::class, 'perusahaan_jurusan', 'perusahaan_id', 'jurusan_id');
    }
}
