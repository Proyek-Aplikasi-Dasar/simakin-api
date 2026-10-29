<?php

namespace App\Http\Controllers;

use App\Http\Requests\Absensi\StoreAbsensiRequest;
use App\Http\Requests\Absensi\UpdateAbsensiRequest;
use App\Http\Requests\Absensi\UploadFotoAbsensiRequest;
use App\Http\Resources\AbsensiSiswaResource;
use App\Models\AbsensiSiswa;
use App\Models\LokasiPkl;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsensiSiswaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AbsensiSiswa::query()
            ->with(['siswa.user', 'korektor']);

        if ($user?->isSiswa()) {
            $query->where('siswa_id', $user->siswa?->id ?? 0);
        } elseif ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->string('tanggal')->toString());
        }
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->string('tanggal_mulai')->toString());
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->string('tanggal_selesai')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $absensi = $query->orderByDesc('tanggal')->orderByDesc('jam_masuk')->paginate(20);

        return AbsensiSiswaResource::collection($absensi);
    }

    public function rekap(Request $request)
    {
        $query = AbsensiSiswa::query();

        if ($request->filled('gelombang_id')) {
            $query->whereHas('siswa.lokasiPkl', fn ($q) => $q->where('gelombang_id', $request->integer('gelombang_id')));
        }
        if ($request->filled('jurusan_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('jurusan_id', $request->integer('jurusan_id')));
        }
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->string('tanggal_mulai')->toString());
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->string('tanggal_selesai')->toString());
        }

        $rows = $query->get();

        // Kelompokkan per siswa.
        $grouped = $rows->groupBy('siswa_id');

        $data = $grouped->map(function ($items) {
            $siswa = $items->first()->siswa;
            $counts = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'libur' => 0,
            ];

            foreach ($items as $item) {
                if (isset($counts[$item->status])) {
                    $counts[$item->status]++;
                }
            }

            $totalHari = $items->count();
            $tidakAbsen = 0; // placeholder; dihitung client/periode

            return [
                'siswa' => $siswa ? [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'nama' => $siswa->user?->name,
                ] : null,
                'hadir' => $counts['hadir'],
                'izin' => $counts['izin'],
                'sakit' => $counts['sakit'],
                'libur' => $counts['libur'],
                'tidak_absen' => $tidakAbsen,
                'total' => $totalHari,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function periodeSiswa(Request $request, Siswa $siswa)
    {
        $user = $request->user();

        if ($user?->isSiswa() && $siswa->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $items = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        $counts = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'libur' => 0];
        foreach ($items as $item) {
            if (isset($counts[$item->status])) {
                $counts[$item->status]++;
            }
        }

        return response()->json([
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->user?->name,
            ],
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rekap' => $counts,
            'data' => AbsensiSiswaResource::collection($items),
        ]);
    }

    public function uploadFoto(UploadFotoAbsensiRequest $request)
    {
        $absensi = AbsensiSiswa::findOrFail($request->absensi_id);

        $user = $request->user();
        if ($user?->isSiswa() && $absensi->siswa?->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $file = $request->file('foto_masuk');
        $path = $this->storeFoto($file, $absensi->siswa->user_id ?? $user->id, $absensi->tanggal->toDateString());

        // Hapus foto lama jika ada.
        if ($absensi->foto_masuk) {
            Storage::disk('public')->delete($absensi->foto_masuk);
        }

        $absensi->update([
            'foto_masuk' => $path,
            'status_submit' => 'success',
        ]);

        return response()->json([
            'message' => 'Foto berhasil diunggah.',
            'data' => new AbsensiSiswaResource($absensi->fresh()->load(['siswa.user', 'korektor'])),
        ]);
    }

    public function show(AbsensiSiswa $absensi)
    {
        $user = request()->user();

        if ($user?->isSiswa() && $absensi->siswa?->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return new AbsensiSiswaResource($absensi->load(['siswa.user', 'korektor']));
    }

    public function store(StoreAbsensiRequest $request)
    {
        $user = $request->user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan.'], 403);
        }

        // Validasi lokasi PKL active & periode praktik.
        $lokasi = LokasiPkl::where('siswa_id', $siswa->id)->where('status', 'active')->first();
        if (! $lokasi) {
            return response()->json([
                'message' => 'Siswa belum memiliki penempatan PKL aktif.',
                'code' => 'GELOMBANG_NOT_ACTIVE',
            ], 422);
        }

        $gelombang = $lokasi->gelombang;
        $today = now()->toDateString();

        if ($gelombang && ($today < $gelombang->praktik_mulai->toDateString() || $today > $gelombang->praktik_selesai->toDateString())) {
            return response()->json([
                'message' => 'Periode praktik gelombang tidak aktif.',
                'code' => 'GELOMBANG_NOT_ACTIVE',
            ], 422);
        }

        $status = $request->input('status', 'hadir');
        $statusSubmit = $request->input('status_submit', 'success');

        // Validasi: hadir wajib foto (kecuali partial save failed).
        $fotoPath = null;
        if ($request->hasFile('foto_masuk')) {
            $fotoPath = $this->storeFoto($request->file('foto_masuk'), $user->id, $today);
        }

        if ($status === 'hadir' && ! $fotoPath && $statusSubmit === 'success') {
            return response()->json([
                'message' => 'Foto wajib diunggah untuk status hadir.',
            ], 422);
        }

        if (in_array($status, ['izin', 'sakit', 'libur'], true) && empty($request->input('keterangan'))) {
            return response()->json([
                'message' => 'Keterangan wajib diisi untuk status izin/sakit/libur.',
            ], 422);
        }

        $tanggal = $request->input('tanggal', $today);

        // Anti-double submit.
        if (AbsensiSiswa::where('siswa_id', $siswa->id)->whereDate('tanggal', $tanggal)->exists()) {
            return response()->json([
                'message' => 'Presensi hari ini sudah dilakukan.',
                'code' => 'ABSENSI_DUPLICATE',
            ], 422);
        }

        try {
            $absensi = AbsensiSiswa::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $tanggal,
                'status' => $status,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'foto_masuk' => $fotoPath,
                'jam_masuk' => now()->format('H:i:s'),
                'keterangan' => $request->input('keterangan'),
                'status_submit' => $statusSubmit,
            ]);
        } catch (QueryException $e) {
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                return response()->json([
                    'message' => 'Presensi hari ini sudah dilakukan.',
                    'code' => 'ABSENSI_DUPLICATE',
                ], 422);
            }
            throw $e;
        }

        $nextAction = $status === 'hadir' ? '/jurnal/create' : null;

        return response()->json([
            'success' => true,
            'message' => 'Presensi berhasil.',
            'data' => new AbsensiSiswaResource($absensi->load(['siswa.user', 'korektor'])),
            'next_action' => $nextAction,
        ], 201);
    }

    public function update(UpdateAbsensiRequest $request, AbsensiSiswa $absensi)
    {
        $user = $request->user();

        if ($user?->isSiswa() && $absensi->siswa?->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($absensi->status_submit !== 'failed') {
            return response()->json([
                'message' => 'Presensi tidak dapat diubah.',
                'code' => 'ABSENSI_EDIT_BLOCKED',
            ], 422);
        }

        $data = $request->validated();

        if (isset($data['foto_masuk']) && $request->hasFile('foto_masuk')) {
            if ($absensi->foto_masuk) {
                Storage::disk('public')->delete($absensi->foto_masuk);
            }
            $data['foto_masuk'] = $this->storeFoto($request->file('foto_masuk'), $user->id, $absensi->tanggal->toDateString());
        }

        // jam_masuk server-side tetap; status_submit jadi success jika update berhasil
        $data['status_submit'] = 'success';

        $absensi->update($data);

        return response()->json([
            'message' => 'Presensi berhasil diperbarui.',
            'data' => new AbsensiSiswaResource($absensi->fresh()->load(['siswa.user', 'korektor'])),
        ]);
    }

    public function destroy(AbsensiSiswa $absensi)
    {
        $user = request()->user();

        if ($user?->isSiswa() && $absensi->siswa?->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($absensi->foto_masuk) {
            Storage::disk('public')->delete($absensi->foto_masuk);
        }

        $absensi->delete();

        return response()->json([
            'message' => 'Presensi berhasil dibatalkan.',
        ]);
    }

    /**
     * Simpan foto ke disk public dan kembalikan path relatif.
     */
    protected function storeFoto(UploadedFile $file, int $userId, string $tanggal): string
    {
        $ext = $file->getClientOriginalExtension();
        $random = Str::random(8);
        $timestamp = now()->timestamp;
        $path = "uploads/presensi/{$tanggal}/{$userId}_{$timestamp}_{$random}.{$ext}";

        Storage::disk('public')->putFileAs("uploads/presensi/{$tanggal}", $file, "{$userId}_{$timestamp}_{$random}.{$ext}");

        return $path;
    }
}
