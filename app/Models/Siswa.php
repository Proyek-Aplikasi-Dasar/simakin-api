<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Relasi ke PengajuanPkl (One-to-Many / HasMany).
     */
    public function pengajuanPkl(): HasMany
    {
        return $this->hasMany(PengajuanPkl::class, 'siswa_id');
    }

    /**
     * Relasi ke LokasiPkl (One-to-Many / HasMany).
     */
    public function lokasiPkl(): HasMany
    {
        return $this->hasMany(LokasiPkl::class, 'siswa_id');
    }

    /**
     * Profil dianggap terkonfirmasi bila timestamp konfirmasi sudah terisi.
     */
    public function isProfilTerkonfirmasi(): bool
    {
        return $this->profil_confirmed_at !== null;
    }

    /**
     * Daftar field wajib konfirmasi yang masih kosong.
     *
     * @return array<int, string>
     */
    public function missingProfilFields(): array
    {
        $this->loadMissing('user');

        $fields = [
            'name' => $this->user?->name,
            'jenis_kelamin' => $this->jenis_kelamin,
            'kelas' => $this->kelas,
            'telp' => $this->telp,
            'email' => $this->user?->email,
            'alamat' => $this->alamat,
            'jurusan_id' => $this->jurusan_id,
            'gelombang_id' => $this->gelombang_id,
            'nama_ortu' => $this->nama_ortu,
            'telp_ortu' => $this->telp_ortu,
            'alamat_ortu' => $this->alamat_ortu,
        ];

        return array_keys(array_filter($fields, fn ($nilai): bool => $nilai === null || $nilai === ''));
    }

    /**
     * Cek seluruh field wajib konfirmasi sudah terisi.
     *
     * Field wajib: name, jenis_kelamin, kelas, telp, email, alamat,
     * jurusan_id, gelombang_id, nama_ortu, telp_ortu, alamat_ortu.
     */
    public function isProfilLengkap(): bool
    {
        return $this->missingProfilFields() === [];
    }
}
