<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StoreWaktuAbsenRequest;
use App\Http\Requests\Master\UpdateWaktuAbsenRequest;
use App\Http\Resources\WaktuAbsenResource;
use App\Models\WaktuAbsen;
use Illuminate\Http\Request;

class WaktuAbsenController extends Controller
{
    public function index(Request $request)
    {
        $waktuAbsen = WaktuAbsen::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where('keterangan', 'like', "%{$search}%");
            })
            ->orderBy('mulai')
            ->paginate(20);

        return WaktuAbsenResource::collection($waktuAbsen);
    }

    public function store(StoreWaktuAbsenRequest $request)
    {
        $waktuAbsen = WaktuAbsen::create($request->validated());

        return response()->json([
            'message' => 'Waktu absen berhasil disimpan.',
            'data' => new WaktuAbsenResource($waktuAbsen),
        ], 201);
    }

    public function update(UpdateWaktuAbsenRequest $request, WaktuAbsen $waktuAbsen)
    {
        $waktuAbsen->update($request->validated());

        return response()->json([
            'message' => 'Waktu absen berhasil diperbarui.',
            'data' => new WaktuAbsenResource($waktuAbsen),
        ]);
    }

    public function destroy(WaktuAbsen $waktuAbsen)
    {
        $waktuAbsen->delete();

        return response()->json([
            'message' => 'Waktu absen berhasil dihapus.',
        ]);
    }
}
