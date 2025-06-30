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
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id('id_pengumuman');
            $table->foreignId('id_user')->constrained('users', 'id')->onDelete('cascade');
            $table->string('judul');
            $table->text('isi_pengumuman');
            $table->enum('ditujukan_ke', ['siswa', 'ortu', 'semua'])->default('semua');
            $table->timestamp('tgl_dibuat')->useCurrent();
            $table->timestamp('tgl_diperbarui')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};