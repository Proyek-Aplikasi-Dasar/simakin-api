<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'kode'])]
class Jurusan extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'jurusan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'kode',
    ];

    /**
     * Relasi ke Kompetensi (One-to-Many / HasMany).
     * Satu jurusan memiliki banyak kompetensi.
     */
    public function kompetensi(): HasMany
    {
        return $this->hasMany(Kompetensi::class, 'jurusan_id');
    }

    /**
     * Relasi ke Perusahaan (Many-to-Many / BelongsToMany) via pivot perusahaan_jurusan.
     */
    public function perusahaan(): BelongsToMany
    {
        return $this->belongsToMany(Perusahaan::class, 'perusahaan_jurusan', 'jurusan_id', 'perusahaan_id');
    }
}
