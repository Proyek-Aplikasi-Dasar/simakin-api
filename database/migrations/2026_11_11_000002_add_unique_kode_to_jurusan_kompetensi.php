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
        // Task 1.10: kode unique validation
        Schema::table('jurusan', function (Blueprint $table) {
            $table->unique('kode');
        });

        Schema::table('kompetensi', function (Blueprint $table) {
            $table->unique('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropUnique(['kode']);
        });

        Schema::table('kompetensi', function (Blueprint $table) {
            $table->dropUnique(['kode']);
        });
    }
};