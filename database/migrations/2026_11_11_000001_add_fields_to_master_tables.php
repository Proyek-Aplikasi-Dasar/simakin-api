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
        // Task 1.8: is_active di master tables
        Schema::table('jurusan', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('kode');
        });

        Schema::table('kompetensi', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('kode');
        });

        Schema::table('sikap', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('deskripsi');
        });

        Schema::table('gelombang', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('praktik_selesai');
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('kuota');
            // Task 1.11: Field tambahan perusahaan (dipindahkan dari Sprint 6)
            $table->unsignedInteger('radius_presensi')->default(100)->after('is_active');
            $table->string('pembimbing_industri', 255)->nullable()->after('radius_presensi');
            $table->string('bidang_usaha', 100)->nullable()->after('pembimbing_industri');
        });

        Schema::table('waktu_absen', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('keterangan');
        });

        // Task 1.9: bidang_keahlian di jurusan
        Schema::table('jurusan', function (Blueprint $table) {
            $table->string('bidang_keahlian', 100)->nullable()->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'bidang_keahlian']);
        });

        Schema::table('kompetensi', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('sikap', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('gelombang', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'radius_presensi', 'pembimbing_industri', 'bidang_usaha']);
        });

        Schema::table('waktu_absen', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};