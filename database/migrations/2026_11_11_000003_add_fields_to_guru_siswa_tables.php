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
        // Task 2.6: Field tambahan guru (telp, jurusan_id, kuota_bimbingan, alamat, is_active)
        Schema::table('guru', function (Blueprint $table) {
            $table->string('telp', 20)->nullable()->after('nip');
            $table->foreignId('jurusan_id')->nullable()->after('telp')->constrained('jurusan')->nullOnDelete();
            $table->unsignedInteger('kuota_bimbingan')->nullable()->after('jurusan_id');
            $table->text('alamat')->nullable()->after('kuota_bimbingan');
            $table->boolean('is_active')->default(true)->after('alamat');
        });

        // Task 2.7: Field tambahan siswa (nisn, tempat_lahir, tanggal_lahir, is_active)
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nisn', 20)->nullable()->after('nis');
            $table->string('tempat_lahir', 100)->nullable()->after('nisn');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->boolean('is_active')->default(true)->after('profil_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropForeign(['jurusan_id']);
            $table->dropColumn(['telp', 'jurusan_id', 'kuota_bimbingan', 'alamat', 'is_active']);
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['nisn', 'tempat_lahir', 'tanggal_lahir', 'is_active']);
        });
    }
};