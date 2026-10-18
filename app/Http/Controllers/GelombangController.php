<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StoreGelombangRequest;
use App\Http\Requests\Master\UpdateGelombangRequest;
use App\Http\Resources\GelombangResource;
use App\Models\Gelombang;
use Illuminate\Http\Request;

class GelombangController extends Controller
{
    public function index(Request $request)
    {
        $gelombang = Gelombang::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn ($w) => $w
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('tahun_ajaran', 'like', "%{$search}%"));
            })
            ->orderByDesc('tahun_ajaran')
            ->orderBy('nama')
            ->paginate(20);

        return GelombangResource::collection($gelombang);
    }

    public function store(StoreGelombangRequest $request)
    {
        $gelombang = Gelombang::create($request->validated());

        return response()->json([
            'message' => 'Gelombang berhasil disimpan.',
            'data' => new GelombangResource($gelombang),
        ], 201);
    }

    public function show(Gelombang $gelombang)
    {
        return new GelombangResource($gelombang);
    }

    public function update(UpdateGelombangRequest $request, Gelombang $gelombang)
    {
        $gelombang->update($request->validated());

        return response()->json([
            'message' => 'Gelombang berhasil diperbarui.',
            'data' => new GelombangResource($gelombang),
        ]);
    }

    public function destroy(Gelombang $gelombang)
    {
        $gelombang->delete();

        return response()->json([
            'message' => 'Gelombang berhasil dihapus.',
        ]);
    }
}
