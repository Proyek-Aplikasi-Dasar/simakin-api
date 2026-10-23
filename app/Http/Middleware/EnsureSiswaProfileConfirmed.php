<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaProfileConfirmed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Hanya siswa yang digerbangi; role lain diteruskan apa adanya.
        if (! $user || ! $user->isSiswa()) {
            return $next($request);
        }

        $siswa = $user->siswa;

        if (! $siswa || $siswa->profil_confirmed_at === null) {
            return response()->json([
                'message' => 'Profil belum dikonfirmasi.',
                'code' => 'PROFIL_BELUM_KONFIRMASI',
            ], 403);
        }

        return $next($request);
    }
}
