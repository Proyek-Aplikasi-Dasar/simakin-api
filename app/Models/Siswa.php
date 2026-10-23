<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'jurusan_id',
    'gelombang_id',
    'nis',
    'jenis_kelamin',
    'kelas',
    'telp',
    'alamat',
    'nama_ortu',
    'telp_ortu',
    'alamat_ortu',
    'profil_confirmed_at',
])]
class Siswa extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'siswa';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'jurusan_id',
        'gelombang_id',
        'nis',
        'jenis_kelamin',
        'kelas',
        'telp',
        'alamat',
        'nama_ortu',
        'telp_ortu',
        'alamat_ortu',
        'profil_confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'profil_confirmed_at' => 'datetime',
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
     * Relasi ke Gelombang (Many-to-One / BelongsTo).
     */
    public function gelombang(): BelongsTo
    {
        return $this->belongsTo(Gelombang::class, 'gelombang_id');
    }

    /**
     * Profil dianggap terkonfirmasi bila timestamp konfirmasi sudah terisi.
     */
    public function isProfilTerkonfirmasi(): bool
    {
        return $this->profil_confirmed_at !== null;
    }

    /**
     * Cek seluruh field wajib konfirmasi sudah terisi.
     *
     * Field wajib: name, jenis_kelamin, kelas, telp, email, alamat,
     * jurusan_id, gelombang_id, nama_ortu, telp_ortu, alamat_ortu.
     */
    public function isProfilLengkap(): bool
    {
        $this->loadMissing('user');

        $wajib = [
            $this->user?->name,
            $this->jenis_kelamin,
            $this->kelas,
            $this->telp,
            $this->user?->email,
            $this->alamat,
            $this->jurusan_id,
            $this->gelombang_id,
            $this->nama_ortu,
            $this->telp_ortu,
            $this->alamat_ortu,
        ];

        foreach ($wajib as $nilai) {
            if ($nilai === null || $nilai === '') {
                return false;
            }
        }

        return true;
    }
}
