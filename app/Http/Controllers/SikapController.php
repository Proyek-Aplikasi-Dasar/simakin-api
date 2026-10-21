<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StoreSikapRequest;
use App\Http\Requests\Master\UpdateSikapRequest;
use App\Http\Resources\SikapResource;
use App\Models\Sikap;
use Illuminate\Http\Request;

class SikapController extends Controller
{
    public function index(Request $request)
    {
        $sikap = Sikap::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where('nama', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->paginate(20);

        return SikapResource::collection($sikap);
    }

    public function store(StoreSikapRequest $request)
    {
        $sikap = Sikap::create($request->validated());

        return response()->json([
            'message' => 'Sikap berhasil disimpan.',
            'data' => new SikapResource($sikap),
        ], 201);
    }

    public function show(Sikap $sikap)
    {
        return new SikapResource($sikap);
    }

    public function update(UpdateSikapRequest $request, Sikap $sikap)
    {
        $sikap->update($request->validated());

        return response()->json([
            'message' => 'Sikap berhasil diperbarui.',
            'data' => new SikapResource($sikap),
        ]);
    }

    public function destroy(Sikap $sikap)
    {
        $sikap->delete();

        return response()->json([
            'message' => 'Sikap berhasil dihapus.',
        ]);
    }
}
