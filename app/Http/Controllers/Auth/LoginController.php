<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Kredensial tidak valid.']]);
        }

        // Kalau request dari mobile (bukan SPA), kembalikan token
        if ($request->header('X-Client') === 'mobile') {
            $token = $user->createToken('android-app')->plainTextToken;
            return response()->json(['user' => $user, 'token' => $token]);
        }

        // Kalau dari SPA web, gunakan session (Sanctum cookie)
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(['user' => $user]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            // Mobile: hapus token yang sedang dipakai
            $token->delete();
        } else {
            // Web: hancurkan session
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Berhasil logout.']);
    }
}
