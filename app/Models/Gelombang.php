<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tahun_ajaran',
    'nama',
    'daftar_mulai',
    'daftar_selesai',
    'praktik_mulai',
    'praktik_selesai',
])]
class Gelombang extends Model
{
    /**
     * Nama tabel (singular, sesuai konvensi proyek).
     *
     * @var string
     */
    protected $table = 'gelombang';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tahun_ajaran',
        'nama',
        'daftar_mulai',
        'daftar_selesai',
        'praktik_mulai',
        'praktik_selesai',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daftar_mulai' => 'date',
            'daftar_selesai' => 'date',
            'praktik_mulai' => 'date',
            'praktik_selesai' => 'date',
        ];
    }
}
