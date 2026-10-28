<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StoreKompetensiRequest;
use App\Http\Requests\Master\UpdateKompetensiRequest;
use App\Http\Resources\KompetensiResource;
use App\Imports\KompetensiImport;
use App\Models\Kompetensi;
use Illuminate\Http\Request;

class KompetensiController extends Controller
{
    public function index(Request $request)
    {
        $kompetensi = Kompetensi::query()
            ->when($request->filled('jurusan_id'), function ($query) use ($request) {
                $query->where('jurusan_id', $request->integer('jurusan_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn($w) => $w
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%"));
            })
            ->with('jurusan')
            ->orderBy('jurusan_id')
            ->orderBy('nama')
            ->paginate(20);

        return KompetensiResource::collection($kompetensi);
    }

    public function store(StoreKompetensiRequest $request)
    {
        $kompetensi = Kompetensi::create($request->validated());

        return response()->json([
            'message' => 'Kompetensi berhasil disimpan.',
            'data' => new KompetensiResource($kompetensi),
        ], 201);
    }

    public function show(Kompetensi $kompetensi)
    {
        return new KompetensiResource($kompetensi->load('jurusan'));
    }

    public function update(UpdateKompetensiRequest $request, Kompetensi $kompetensi)
    {
        $kompetensi->update($request->validated());

        return response()->json([
            'message' => 'Kompetensi berhasil diperbarui.',
            'data' => new KompetensiResource($kompetensi->load('jurusan')),
        ]);
    }

    public function destroy(Kompetensi $kompetensi)
    {
        $kompetensi->delete();

        return response()->json([
            'message' => 'Kompetensi berhasil dihapus.',
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = (new KompetensiImport)->import($request->file('file')->getRealPath());

        return response()->json($result->toArray());
    }
}
