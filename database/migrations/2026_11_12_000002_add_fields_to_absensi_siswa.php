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
        // Task 4.6: Jam shift di absensi (diisi siswa saat presensi)
        Schema::table('absensi_siswa', function (Blueprint $table) {
            $table->time('jam_shift_mulai')->nullable()->after('jam_masuk');
            $table->time('jam_shift_selesai')->nullable()->after('jam_shift_mulai');
        });

        // Task 4.7: Berkas pendukung izin (upload surat izin/sakit)
        Schema::table('absensi_siswa', function (Blueprint $table) {
            $table->string('berkas_pendukung', 500)->nullable()->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_siswa', function (Blueprint $table) {
            $table->dropColumn(['jam_shift_mulai', 'jam_shift_selesai', 'berkas_pendukung']);
        });
    }
};