<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->enum('sumber_pilihan', ['daftar_perusahaan_mitra', 'daftar_perusahaan_siswa'])->nullable()->after('gelombang_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn('sumber_pilihan');
        });
    }
};
