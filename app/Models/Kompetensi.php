<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['jurusan_id', 'nama', 'kode'])]
class Kompetensi extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'kompetensi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jurusan_id',
        'nama',
        'kode',
    ];

    /**
     * Relasi ke Jurusan (Many-to-One / BelongsTo).
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }
}
