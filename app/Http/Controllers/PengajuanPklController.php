<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pkl\ApprovePengajuanPklRequest;
use App\Http\Requests\Pkl\BulkApprovePengajuanPklRequest;
use App\Http\Requests\Pkl\RejectPengajuanPklRequest;
use App\Http\Requests\Pkl\StorePengajuanPklRequest;
use App\Http\Resources\PengajuanPklResource;
use App\Models\Gelombang;
use App\Models\LokasiPkl;
use App\Models\PengajuanPkl;
use App\Models\Perusahaan;
use App\Models\Siswa;
use App\Notifications\PengajuanPklReviewedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanPklController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PengajuanPkl::query()
            ->with(['siswa.user', 'siswa.jurusan', 'perusahaan', 'gelombang', 'verifier']);

        // Siswa hanya melihat miliknya.
        if ($user?->isSiswa()) {
            $siswa = $user->siswa;
            $query->where('siswa_id', $siswa?->id ?? 0);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->integer('siswa_id'));
        }
        if ($request->filled('perusahaan_id')) {
            $query->where('perusahaan_id', $request->integer('perusahaan_id'));
        }
        if ($request->filled('gelombang_id')) {
            $query->where('gelombang_id', $request->integer('gelombang_id'));
        }

        $pengajuan = $query->orderByDesc('created_at')->paginate(20);

        return PengajuanPklResource::collection($pengajuan);
    }

    public function show(PengajuanPkl $pengajuanPkl)
    {
        $user = request()->user();

        if ($user?->isSiswa() && $pengajuanPkl->siswa_id !== $user->siswa?->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return new PengajuanPklResource($pengajuanPkl->load(['siswa.user', 'siswa.jurusan', 'perusahaan', 'gelombang', 'verifier']));
    }

    public function store(StorePengajuanPklRequest $request)
    {
        $user = $request->user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan.'], 403);
        }

        $gelombang = Gelombang::find($request->gelombang_id);
        $today = now()->toDateString();

        // Validasi gelombang aktif (periode pendaftaran).
        if (! $gelombang || $today < $gelombang->daftar_mulai->toDateString() || $today > $gelombang->daftar_selesai->toDateString()) {
            return response()->json([
                'message' => 'Pendaftaran gelombang tidak aktif.',
                'code' => 'GELOMBANG_NOT_ACTIVE',
            ], 422);
        }

        // Siswa sudah punya lokasi PKL active.
        if (LokasiPkl::where('siswa_id', $siswa->id)->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Siswa sudah memiliki penempatan PKL aktif.',
                'code' => 'SISWA_ALREADY_PLACED',
            ], 422);
        }

        // Siswa sudah punya pengajuan pending di gelombang ini.
        if (PengajuanPkl::where('siswa_id', $siswa->id)
            ->where('gelombang_id', $gelombang->id)
            ->where('status', 'pending')
            ->exists()
        ) {
            return response()->json([
                'message' => 'Sudah ada pengajuan PKL yang pending.',
                'code' => 'PENGAJUAN_DUPLICATE',
            ], 422);
        }

        $perusahaan = Perusahaan::with('jurusan')->find($request->perusahaan_id);

        // Validasi jurusan siswa sesuai perusahaan.
        if ($siswa->jurusan_id && ! $perusahaan->jurusan->contains('id', $siswa->jurusan_id)) {
            return response()->json([
                'message' => 'Jurusan siswa tidak sesuai dengan perusahaan.',
            ], 422);
        }

        // Validasi kuota perusahaan.
        $terpakai = LokasiPkl::where('perusahaan_id', $perusahaan->id)
            ->where('gelombang_id', $gelombang->id)
            ->whereIn('status', ['pending', 'active'])
            ->count();

        if ($perusahaan->kuota !== null && $terpakai >= $perusahaan->kuota) {
            return response()->json([
                'message' => 'Kuota perusahaan sudah penuh.',
                'code' => 'PERUSAHAAN_KUOTA_PENUH',
            ], 422);
        }

        $pengajuan = PengajuanPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan PKL berhasil dibuat.',
            'data' => new PengajuanPklResource($pengajuan->load(['siswa.user', 'siswa.jurusan', 'perusahaan', 'gelombang'])),
        ], 201);
    }

    public function approve(ApprovePengajuanPklRequest $request, PengajuanPkl $pengajuanPkl)
    {
        if ($pengajuanPkl->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $pengajuan = DB::transaction(function () use ($request, $pengajuanPkl) {
            $pengajuanPkl->update([
                'status' => 'approved',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            // Siswa sudah punya lokasi active? (guard).
            if (LokasiPkl::where('siswa_id', $pengajuanPkl->siswa_id)->where('status', 'active')->exists()) {
                throw new \RuntimeException('SISWA_ALREADY_PLACED');
            }

            LokasiPkl::create([
                'siswa_id' => $pengajuanPkl->siswa_id,
                'guru_id' => $request->guru_pembimbing_id,
                'perusahaan_id' => $pengajuanPkl->perusahaan_id,
                'gelombang_id' => $pengajuanPkl->gelombang_id,
                'status' => 'active',
            ]);

            return $pengajuanPkl->fresh()->load(['siswa.user', 'siswa.jurusan', 'perusahaan', 'gelombang', 'verifier']);
        });

        $siswaUser = $pengajuan->siswa?->user;
        if ($siswaUser) {
            $siswaUser->notify(new PengajuanPklReviewedNotification($pengajuan, 'approved'));
        }

        return response()->json([
            'message' => 'Pengajuan PKL disetujui.',
            'data' => new PengajuanPklResource($pengajuan),
        ]);
    }

    public function reject(RejectPengajuanPklRequest $request, PengajuanPkl $pengajuanPkl)
    {
        if ($pengajuanPkl->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan sudah diproses.'], 422);
        }

        $pengajuanPkl->update([
            'status' => 'rejected',
            'alasan_penolakan' => $request->alasan_penolakan,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $siswaUser = $pengajuanPkl->siswa?->user;
        if ($siswaUser) {
            $siswaUser->notify(new PengajuanPklReviewedNotification($pengajuanPkl->fresh(), 'rejected', $request->alasan_penolakan));
        }

        return response()->json([
            'message' => 'Pengajuan PKL ditolak.',
            'data' => new PengajuanPklResource($pengajuanPkl->fresh()->load(['siswa.user', 'siswa.jurusan', 'perusahaan', 'gelombang', 'verifier'])),
        ]);
    }

    public function bulkApprove(BulkApprovePengajuanPklRequest $request)
    {
        $ids = $request->ids;
        $guruId = $request->guru_pembimbing_id;
        $approved = [];
        $failed = [];

        DB::transaction(function () use ($ids, $guruId, &$approved, &$failed) {
            foreach ($ids as $id) {
                $pengajuan = PengajuanPkl::find($id);

                if (! $pengajuan || $pengajuan->status !== 'pending') {
                    $failed[] = ['id' => $id, 'error' => 'Pengajuan tidak ditemukan atau sudah diproses.'];

                    continue;
                }

                // Guard: siswa sudah placed.
                if (LokasiPkl::where('siswa_id', $pengajuan->siswa_id)->where('status', 'active')->exists()) {
                    $failed[] = ['id' => $id, 'error' => 'Siswa sudah memiliki penempatan aktif.'];

                    continue;
                }

                $pengajuan->update([
                    'status' => 'approved',
                    'verified_by' => request()->user()->id,
                    'verified_at' => now(),
                ]);

                LokasiPkl::create([
                    'siswa_id' => $pengajuan->siswa_id,
                    'guru_id' => $guruId,
                    'perusahaan_id' => $pengajuan->perusahaan_id,
                    'gelombang_id' => $pengajuan->gelombang_id,
                    'status' => 'active',
                ]);

                $approved[] = $id;
            }
        });

        // Kirim notifikasi setelah transaksi sukses.
        foreach ($approved as $id) {
            $pengajuan = PengajuanPkl::find($id);
            if ($pengajuan?->siswa?->user) {
                $pengajuan->siswa->user->notify(new PengajuanPklReviewedNotification($pengajuan, 'approved'));
            }
        }

        return response()->json([
            'message' => 'Bulk approve selesai.',
            'approved' => $approved,
            'failed' => $failed,
        ]);
    }

    public function destroy(PengajuanPkl $pengajuanPkl)
    {
        $user = request()->user();

        if (! $user?->isSiswa() || $pengajuanPkl->siswa_id !== $user->siswa?->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($pengajuanPkl->status !== 'pending') {
            return response()->json(['message' => 'Hanya pengajuan pending yang bisa dibatalkan.'], 422);
        }

        $pengajuanPkl->delete();

        return response()->json([
            'message' => 'Pengajuan PKL dibatalkan.',
        ]);
    }
}
