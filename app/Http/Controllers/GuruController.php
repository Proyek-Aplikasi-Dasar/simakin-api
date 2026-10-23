<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreGuruRequest;
use App\Http\Requests\User\UpdateGuruRequest;
use App\Http\Resources\GuruResource;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $guru = Guru::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($w) use ($search) {
                    $w->where('nip', 'like', "%{$search}%")
                        ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nip')
            ->paginate(20);

        return GuruResource::collection($guru);
    }

    public function store(StoreGuruRequest $request)
    {
        $data = $request->validated();

        $guru = DB::transaction(function () use ($data) {
            // Identitas login guru selalu NIP (lihat AGENTS.md & 003-api-endpoints.md §9).
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['nip'],
                'email' => null,
                'password' => $data['password'],
                'role_id' => 2,
            ]);

            return Guru::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
            ])->load('user');
        });

        return response()->json([
            'message' => 'Guru berhasil disimpan.',
            'data' => new GuruResource($guru),
        ], 201);
    }

    public function show(Guru $guru)
    {
        return new GuruResource($guru->load('user'));
    }

    public function update(UpdateGuruRequest $request, Guru $guru)
    {
        $data = $request->validated();

        $guru = DB::transaction(function () use ($data, $guru) {
            $user = $guru->user;
            $userData = [];

            if (array_key_exists('name', $data)) {
                $userData['name'] = $data['name'];
            }

            // NIP adalah identitas login; username tidak boleh menyimpang dari NIP.
            if (array_key_exists('nip', $data) && ! empty($data['nip'])) {
                $userData['username'] = $data['nip'];
            }

            if (array_key_exists('password', $data) && ! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if (! empty($userData)) {
                $user->update($userData);
            }

            $guruData = [];

            if (array_key_exists('nip', $data)) {
                $guruData['nip'] = $data['nip'];
            }

            if (array_key_exists('mata_pelajaran', $data)) {
                $guruData['mata_pelajaran'] = $data['mata_pelajaran'];
            }

            if (! empty($guruData)) {
                $guru->update($guruData);
            }

            return $guru->fresh()->load('user');
        });

        return response()->json([
            'message' => 'Guru berhasil diperbarui.',
            'data' => new GuruResource($guru),
        ]);
    }

    public function destroy(Guru $guru)
    {
        DB::transaction(function () use ($guru) {
            $user = $guru->user;

            $guru->delete();

            if ($user) {
                // Hard delete (tanpa soft delete) agar username/NIP bisa dipakai ulang.
                $user->tokens()->delete();
                $user->delete();
            }
        });

        return response()->json([
            'message' => 'Guru berhasil dihapus.',
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = (new GuruImport)->import($request->file('file')->getRealPath());

        return response()->json($result->toArray());
    }
}
