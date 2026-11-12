<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Task 3.5: Catatan saat approve pengajuan (bukan hanya saat reject)
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('alasan_penolakan');
        });

        // Task 3.6: Tanggal mulai/selesai di lokasi_pkl
        Schema::table('lokasi_pkl', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('status');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_pkl', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });

        Schema::table('lokasi_pkl', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
