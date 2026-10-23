<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreSiswaRequest;
use App\Http\Requests\User\UpdateProfilSiswaRequest;
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

    /**
     * Profil siswa yang sedang login beserta status onboarding.
     */
    public function me(Request $request)
    {
        $siswa = $request->user()->siswa;

        if (! $siswa) {
            return response()->json([
                'message' => 'Profil siswa tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $this->profilPayload($siswa),
        ]);
    }

    /**
     * Siswa melengkapi/mengubah profilnya selama belum dikonfirmasi.
     */
    public function updateProfil(UpdateProfilSiswaRequest $request)
    {
        $siswa = $request->user()->siswa;

        if (! $siswa) {
            return response()->json([
                'message' => 'Profil siswa tidak ditemukan.',
            ], 404);
        }

        if ($siswa->isProfilTerkonfirmasi()) {
            return response()->json([
                'message' => 'Profil sudah dikonfirmasi. Perubahan hanya dapat dilakukan oleh admin.',
                'code' => 'PROFIL_SUDAH_DIKONFIRMASI',
            ], 422);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $siswa) {
            $userData = Arr::only($data, ['name', 'email']);

            if (! empty($userData)) {
                $siswa->user->update($userData);
            }

            $siswaData = Arr::only($data, self::PROFIL_FIELDS);

            if (! empty($siswaData)) {
                $siswa->update($siswaData);
            }
        });

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->profilPayload($siswa->fresh()),
        ]);
    }

    /**
     * Konfirmasi profil siswa setelah seluruh field wajib terisi.
     */
    public function konfirmasi(Request $request)
    {
        $siswa = $request->user()->siswa;

        if (! $siswa) {
            return response()->json([
                'message' => 'Profil siswa tidak ditemukan.',
            ], 404);
        }

        // Idempotent: konfirmasi ulang tidak mengubah timestamp.
        if ($siswa->isProfilTerkonfirmasi()) {
            return response()->json([
                'message' => 'Profil sudah dikonfirmasi sebelumnya.',
                'data' => $this->profilPayload($siswa),
            ]);
        }

        $missing = $siswa->missingProfilFields();

        if ($missing !== []) {
            $errors = [];

            foreach ($missing as $field) {
                $errors[$field] = ['Field ini wajib diisi sebelum konfirmasi.'];
            }

            return response()->json([
                'message' => 'Profil belum lengkap. Lengkapi data berikut sebelum konfirmasi.',
                'code' => 'PROFIL_BELUM_LENGKAP',
                'errors' => $errors,
            ], 422);
        }

        $siswa->update(['profil_confirmed_at' => now()]);

        return response()->json([
            'message' => 'Profil berhasil dikonfirmasi.',
            'data' => $this->profilPayload($siswa->fresh()),
        ]);
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

    /**
     * Payload profil siswa untuk endpoint onboarding.
     *
     * @return array<string, mixed>
     */
    private function profilPayload(Siswa $siswa): array
    {
        $siswa->loadMissing(['user', 'jurusan', 'gelombang']);

        return [
            'user' => [
                'id' => $siswa->user->id,
                'name' => $siswa->user->name,
                'username' => $siswa->user->username,
                'email' => $siswa->user->email,
            ],
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'kelas' => $siswa->kelas,
                'telp' => $siswa->telp,
                'alamat' => $siswa->alamat,
                'nama_ortu' => $siswa->nama_ortu,
                'telp_ortu' => $siswa->telp_ortu,
                'alamat_ortu' => $siswa->alamat_ortu,
            ],
            'akademik' => [
                'jurusan' => $siswa->jurusan ? [
                    'id' => $siswa->jurusan->id,
                    'nama' => $siswa->jurusan->nama,
                    'kode' => $siswa->jurusan->kode,
                ] : null,
                'gelombang' => $siswa->gelombang ? [
                    'id' => $siswa->gelombang->id,
                    'nama' => $siswa->gelombang->nama,
                    'tahun_ajaran' => $siswa->gelombang->tahun_ajaran,
                ] : null,
            ],
            'profil_confirmed_at' => $siswa->profil_confirmed_at,
            'profil_terkonfirmasi' => $siswa->isProfilTerkonfirmasi(),
            'profil_lengkap' => $siswa->isProfilLengkap(),
        ];
    }
}
