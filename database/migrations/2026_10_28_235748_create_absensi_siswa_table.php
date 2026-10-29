<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'libur'])->default('hadir');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('foto_masuk', 500)->nullable();
            $table->time('jam_masuk')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status_submit', ['success', 'failed'])->default('success');
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal'], 'absensi_unique_siswa_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswa');
    }
};
