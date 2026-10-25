<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['role', 'guru', 'siswa']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        return new UserResource($user->load(['role', 'guru', 'siswa']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Jaga konsistensi identitas: jika user terkait guru, username/NIP sebaiknya tetap.
        // Jika user terkait siswa, username/NIS dipertahankan lewat relasi; izinkan update
        // sesuai kontrak (name, username, role_id) dengan validasi unik.
        $user->update($data);

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->load(['role', 'guru', 'siswa'])),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        $user->update(['password' => $request->password]);

        // Cabut seluruh token/sesi milik user (jalur lupa kata sandi).
        $user->tokens()->delete();

        if ($request->hasSession()) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return response()->json([
            'message' => 'Kata sandi berhasil direset.',
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // Tidak boleh menghapus akun sendiri.
        if ($request->user()?->id === $user->id) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun sendiri.',
                'code' => 'CANNOT_DELETE_SELF',
            ], 422);
        }

        // Soft delete user. Untuk konsistensi dengan pola B2, cabut token terlebih dahulu.
        $user->tokens()->delete();
        if ($request->hasSession()) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }
}
