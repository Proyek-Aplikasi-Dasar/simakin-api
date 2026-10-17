<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak sesuai.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        $this->revokeOtherSessions($request, $user);

        return response()->json([
            'message' => 'Kata sandi berhasil diubah.',
        ]);
    }

    private function revokeOtherSessions(UpdatePasswordRequest $request, $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken instanceof PersonalAccessToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        } else {
            $user->tokens()->delete();
        }

        if ($request->hasSession()) {
            $currentSessionId = $request->session()->getId();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();

            $request->session()->regenerate();
        }
    }
}
