<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StorePerusahaanRequest;
use App\Http\Requests\Master\UpdatePerusahaanRequest;
use App\Http\Resources\PerusahaanResource;
use App\Imports\PerusahaanImport;
use App\Models\Jurusan;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerusahaanController extends Controller
{
    public function index(Request $request)
    {
        $perusahaan = Perusahaan::query()
            ->when($request->filled('jurusan_id'), function ($query) use ($request) {
                $query->whereHas('jurusan', fn($w) => $w->where('jurusan.id', $request->integer('jurusan_id')));
            })
            ->when($request->boolean('kuota_tersedia'), function ($query) {
                $query->where(function ($w) {
                    $w->whereNull('kuota')
                        ->orWhereRaw('kuota > (select count(*) from lokasi_pkl where lokasi_pkl.perusahaan_id = perusahaan.id and lokasi_pkl.status in (\'pending\', \'active\'))');
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn($w) => $w
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%"));
            })
            ->with('jurusan')
            ->orderBy('nama')
            ->paginate(20);

        return PerusahaanResource::collection($perusahaan);
    }

    public function store(StorePerusahaanRequest $request)
    {
        $data = $request->validated();
        $jurusanIds = $data['jurusan_ids'] ?? null;
        unset($data['jurusan_ids']);

        $perusahaan = Perusahaan::create($data);

        if ($jurusanIds !== null) {
            $perusahaan->jurusan()->sync($jurusanIds);
        }

        return response()->json([
            'message' => 'Perusahaan berhasil disimpan.',
            'data' => new PerusahaanResource($perusahaan->load('jurusan')),
        ], 201);
    }

    public function show(Perusahaan $perusahaan)
    {
        return new PerusahaanResource($perusahaan->load('jurusan'));
    }

    public function update(UpdatePerusahaanRequest $request, Perusahaan $perusahaan)
    {
        $data = $request->validated();
        $jurusanIds = $data['jurusan_ids'] ?? null;
        unset($data['jurusan_ids']);

        $perusahaan->update($data);

        if ($jurusanIds !== null) {
            $perusahaan->jurusan()->sync($jurusanIds);
        }

        return response()->json([
            'message' => 'Perusahaan berhasil diperbarui.',
            'data' => new PerusahaanResource($perusahaan->load('jurusan')),
        ]);
    }

    public function destroy(Perusahaan $perusahaan)
    {
        $perusahaan->delete();

        return response()->json([
            'message' => 'Perusahaan berhasil dihapus.',
        ]);
    }

    public function attachJurusan(Request $request, Perusahaan $perusahaan)
    {
        $data = $request->validate([
            'jurusan_id' => ['required', Rule::exists('jurusan', 'id')],
        ]);

        $perusahaan->jurusan()->syncWithoutDetaching($data['jurusan_id']);

        return response()->json([
            'message' => 'Jurusan berhasil ditambahkan ke perusahaan.',
            'data' => new PerusahaanResource($perusahaan->load('jurusan')),
        ]);
    }

    public function detachJurusan(Perusahaan $perusahaan, Jurusan $jurusan)
    {
        $terpasang = $perusahaan->jurusan()->where('jurusan.id', $jurusan->id)->exists();

        if (! $terpasang) {
            return response()->json([
                'message' => 'Jurusan tidak terpasang di perusahaan ini.',
            ], 404);
        }

        $perusahaan->jurusan()->detach($jurusan->id);

        return response()->json([
            'message' => 'Jurusan berhasil dilepas dari perusahaan.',
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = (new PerusahaanImport)->import($request->file('file')->getRealPath());

        return response()->json($result->toArray());
    }
}
