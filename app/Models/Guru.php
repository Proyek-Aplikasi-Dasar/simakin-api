<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'guru';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nip',
        'telp',
        'jurusan_id',
        'kuota_bimbingan',
        'alamat',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'kuota_bimbingan' => 'integer',
        ];
    }

    /**
     * Relasi ke User (Many-to-One / BelongsTo).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Jurusan (Many-to-One / BelongsTo).
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /**
     * Relasi ke LokasiPkl (One-to-Many / HasMany).
     */
    public function lokasiPkl(): HasMany
    {
        return $this->hasMany(LokasiPkl::class, 'guru_id');
    }
}
