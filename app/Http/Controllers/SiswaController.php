<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreSiswaRequest;
use App\Http\Requests\User\UpdateSiswaRequest;
use App\Http\Resources\SiswaResource;
use App\Imports\SiswaImport;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SiswaController extends Controller
{
    /**
     * Field profil siswa yang boleh diisi admin (di luar identitas user).
     *
     * @var array<int, string>
     */
    private const PROFIL_FIELDS = [
        'jurusan_id',
        'gelombang_id',
        'jenis_kelamin',
        'kelas',
        'telp',
        'alamat',
        'nama_ortu',
        'telp_ortu',
        'alamat_ortu',
    ];

    public function index(Request $request)
    {
        $siswa = Siswa::query()
            ->with(['user', 'jurusan', 'gelombang'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($w) use ($search) {
                    $w->where('nis', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('jurusan_id'), fn ($query) => $query->where('jurusan_id', $request->integer('jurusan_id')))
            ->when($request->filled('gelombang_id'), fn ($query) => $query->where('gelombang_id', $request->integer('gelombang_id')))
            ->when($request->filled('kelas'), fn ($query) => $query->where('kelas', $request->string('kelas')->toString()))
            ->orderBy('nis')
            ->paginate(20);

        return SiswaResource::collection($siswa);
    }

    public function store(StoreSiswaRequest $request)
    {
        $data = $request->validated();

        $siswa = DB::transaction(function () use ($data) {
            // Identitas login siswa selalu NIS (lihat AGENTS.md & 002-database-schema.md §11).
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['nis'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role_id' => 3,
            ]);

            return Siswa::create([
                'user_id' => $user->id,
                'nis' => $data['nis'],
                ...Arr::only($data, self::PROFIL_FIELDS),
            ])->load(['user', 'jurusan', 'gelombang']);
        });

        return response()->json([
            'message' => 'Siswa berhasil disimpan.',
            'data' => new SiswaResource($siswa),
        ], 201);
    }

    public function show(Siswa $siswa)
    {
        return new SiswaResource($siswa->load(['user', 'jurusan', 'gelombang']));
    }

    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        $data = $request->validated();

        $siswa = DB::transaction(function () use ($data, $siswa) {
            $user = $siswa->user;
            $userData = [];

            if (array_key_exists('name', $data)) {
                $userData['name'] = $data['name'];
            }

            // NIS adalah identitas login; username tidak boleh menyimpang dari NIS.
            if (array_key_exists('nis', $data) && ! empty($data['nis'])) {
                $userData['username'] = $data['nis'];
            }

            if (array_key_exists('email', $data)) {
                $userData['email'] = $data['email'];
            }

            if (array_key_exists('password', $data) && ! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if (! empty($userData)) {
                $user->update($userData);
            }

            // Admin tetap boleh mengubah profil setelah konfirmasi; timestamp tidak direset.
            $siswaData = Arr::only($data, self::PROFIL_FIELDS);

            if (array_key_exists('nis', $data)) {
                $siswaData['nis'] = $data['nis'];
            }

            if (! empty($siswaData)) {
                $siswa->update($siswaData);
            }

            return $siswa->fresh()->load(['user', 'jurusan', 'gelombang']);
        });

        return response()->json([
            'message' => 'Siswa berhasil diperbarui.',
            'data' => new SiswaResource($siswa),
        ]);
    }

    public function destroy(Siswa $siswa)
    {
        DB::transaction(function () use ($siswa) {
            // Soft delete siswa agar histori transaksional (presensi/jurnal/penilaian)
            // pada sprint berikutnya tetap utuh. Akun user dipertahankan agar data
            // referensi tidak ikut terhapus karena FK CASCADE; token dicabut agar
            // sesi aktif tidak bisa dipakai lagi.
            $siswa->user?->tokens()->delete();
            $siswa->delete();
        });

        return response()->json([
            'message' => 'Siswa berhasil dihapus.',
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = (new SiswaImport)->import($request->file('file')->getRealPath());

        return response()->json($result->toArray());
    }
}
