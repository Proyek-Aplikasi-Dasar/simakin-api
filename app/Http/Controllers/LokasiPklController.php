<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pkl\StoreLokasiPklRequest;
use App\Http\Requests\Pkl\UpdateLokasiPklRequest;
use App\Http\Requests\Pkl\UpdateStatusLokasiPklRequest;
use App\Http\Resources\LokasiPklResource;
use App\Models\LokasiPkl;
use Illuminate\Http\Request;

class LokasiPklController extends Controller
{
    public function index(Request $request)
    {
        $lokasi = LokasiPkl::query()
            ->with(['siswa.user', 'guru.user', 'perusahaan', 'gelombang'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('siswa_id'), fn($q) => $q->where('siswa_id', $request->integer('siswa_id')))
            ->when($request->filled('guru_id'), fn($q) => $q->where('guru_id', $request->integer('guru_id')))
            ->when($request->filled('perusahaan_id'), fn($q) => $q->where('perusahaan_id', $request->integer('perusahaan_id')))
            ->when($request->filled('gelombang_id'), fn($q) => $q->where('gelombang_id', $request->integer('gelombang_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->whereHas('siswa.user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return LokasiPklResource::collection($lokasi);
    }

    public function show(LokasiPkl $lokasiPkl)
    {
        $user = request()->user();

        if ($user?->isSiswa() && $lokasiPkl->siswa_id !== $user->siswa?->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return new LokasiPklResource($lokasiPkl->load(['siswa.user', 'guru.user', 'perusahaan', 'gelombang']));
    }

    public function store(StoreLokasiPklRequest $request)
    {
        $data = $request->validated();

        if (LokasiPkl::where('siswa_id', $data['siswa_id'])->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Siswa sudah memiliki penempatan PKL aktif.',
                'code' => 'SISWA_ALREADY_PLACED',
            ], 422);
        }

        $lokasi = LokasiPkl::create(array_merge(['status' => 'pending'], $data));

        return response()->json([
            'message' => 'Lokasi PKL berhasil dibuat.',
            'data' => new LokasiPklResource($lokasi->load(['siswa.user', 'guru.user', 'perusahaan', 'gelombang'])),
        ], 201);
    }

    public function update(UpdateLokasiPklRequest $request, LokasiPkl $lokasiPkl)
    {
        $data = $request->validated();

        // Jika siswa diubah, cek placed.
        if (isset($data['siswa_id']) && $data['siswa_id'] !== $lokasiPkl->siswa_id) {
            if (LokasiPkl::where('siswa_id', $data['siswa_id'])->where('status', 'active')->exists()) {
                return response()->json([
                    'message' => 'Siswa sudah memiliki penempatan PKL aktif.',
                    'code' => 'SISWA_ALREADY_PLACED',
                ], 422);
            }
        }

        $lokasiPkl->update($data);

        return response()->json([
            'message' => 'Lokasi PKL berhasil diperbarui.',
            'data' => new LokasiPklResource($lokasiPkl->fresh()->load(['siswa.user', 'guru.user', 'perusahaan', 'gelombang'])),
        ]);
    }

    public function updateStatus(UpdateStatusLokasiPklRequest $request, LokasiPkl $lokasiPkl)
    {
        $newStatus = $request->status;
        $current = $lokasiPkl->status;

        $allowed = [
            'pending' => ['active', 'cancelled'],
            'active' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (! in_array($newStatus, $allowed[$current] ?? [], true)) {
            return response()->json([
                'message' => 'Transisi status tidak valid.',
            ], 422);
        }

        // Jika transisi ke active, cek placed (kecuali lokasi ini sendiri).
        if ($newStatus === 'active') {
            $exists = LokasiPkl::where('siswa_id', $lokasiPkl->siswa_id)
                ->where('status', 'active')
                ->where('id', '!=', $lokasiPkl->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Siswa sudah memiliki penempatan PKL aktif.',
                    'code' => 'SISWA_ALREADY_PLACED',
                ], 422);
            }
        }

        $lokasiPkl->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status lokasi PKL diperbarui.',
            'data' => new LokasiPklResource($lokasiPkl->fresh()->load(['siswa.user', 'guru.user', 'perusahaan', 'gelombang'])),
        ]);
    }

    public function destroy(LokasiPkl $lokasiPkl)
    {
        $lokasiPkl->delete();

        return response()->json([
            'message' => 'Lokasi PKL berhasil dihapus.',
        ]);
    }
}
