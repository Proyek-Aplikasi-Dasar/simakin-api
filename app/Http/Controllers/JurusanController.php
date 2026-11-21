<?php

namespace App\Http\Controllers;

use App\Exports\JurusanExport;
use App\Http\Requests\Master\StoreJurusanRequest;
use App\Http\Requests\Master\UpdateJurusanRequest;
use App\Http\Resources\JurusanResource;
use App\Http\Resources\KompetensiResource;
use App\Imports\JurusanImport;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function kompetensi(Jurusan $jurusan)
    {
        return KompetensiResource::collection($jurusan->kompetensi);
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

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = (new JurusanImport)->import(
            $request->file('file')->getRealPath(),
            $request->file('file')->getClientOriginalExtension()
        );

        return response()->json($result->toArray());
    }

    public function export(Request $request)
    {
        $items = Jurusan::orderBy('nama')->get();

        $filename = 'jurusan-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new JurusanExport($items),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
