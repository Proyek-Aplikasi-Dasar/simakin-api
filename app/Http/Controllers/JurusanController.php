<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\StoreJurusanRequest;
use App\Http\Requests\Master\UpdateJurusanRequest;
use App\Http\Resources\JurusanResource;
use App\Models\Jurusan;
use Illuminate\Http\Request;

class JurusanController extends Controller
{
    public function index(Request $request)
    {
        $jurusan = Jurusan::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(fn ($w) => $w
                    ->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%"));
            })
            ->orderBy('nama')
            ->paginate(20);

        return JurusanResource::collection($jurusan);
    }

    public function store(StoreJurusanRequest $request)
    {
        $jurusan = Jurusan::create($request->validated());

        return response()->json([
            'message' => 'Jurusan berhasil disimpan.',
            'data' => new JurusanResource($jurusan),
        ], 201);
    }

    public function show(Jurusan $jurusan)
    {
        return new JurusanResource($jurusan);
    }

    public function update(UpdateJurusanRequest $request, Jurusan $jurusan)
    {
        $jurusan->update($request->validated());

        return response()->json([
            'message' => 'Jurusan berhasil diperbarui.',
            'data' => new JurusanResource($jurusan),
        ]);
    }

    public function destroy(Jurusan $jurusan)
    {
        $jurusan->delete();

        return response()->json([
            'message' => 'Jurusan berhasil dihapus.',
        ]);
    }
}
