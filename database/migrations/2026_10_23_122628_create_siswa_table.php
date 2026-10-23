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
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->restrictOnDelete();
            $table->foreignId('gelombang_id')->nullable()->constrained('gelombang')->restrictOnDelete();
            $table->string('nis', 20)->unique();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('kelas', 20)->nullable();
            $table->string('telp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nama_ortu', 255)->nullable();
            $table->string('telp_ortu', 20)->nullable();
            $table->text('alamat_ortu')->nullable();
            $table->timestamp('profil_confirmed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
