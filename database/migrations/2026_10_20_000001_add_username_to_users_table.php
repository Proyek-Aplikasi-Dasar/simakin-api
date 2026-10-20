<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Autentikasi berpindah dari email ke username:
     * - Tambah kolom username (unique) sebagai identitas login.
     * - Isi username lama dari local-part email (backfill untuk data dev).
     * - Email menjadi nullable (tidak dipakai untuk login di PAD 1).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill: username = bagian sebelum '@' pada email (data lama)
        User::query()
            ->whereNull('username')
            ->whereNotNull('email')
            ->get()
            ->each(function (User $user) {
                $username = Str::before($user->email, '@');
                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            });

        // Setelah backfill, username wajib diisi
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });

        // Email tidak lagi wajib (dipertahankan, tapi tidak dipakai untuk login)
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });

        // Kembalikan email jadi wajib hanya jika tidak ada baris bernilai null
        $hasNullEmail = DB::table('users')->whereNull('email')->exists();

        if (! $hasNullEmail) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });
        }
    }
};
