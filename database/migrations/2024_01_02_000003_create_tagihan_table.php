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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id('id_tagihan');
            $table->foreignId('id_siswa')->constrained('siswa', 'id_siswa')->onDelete('cascade');
            $table->string('nama_tagihan');
            $table->decimal('jumlah_tagihan', 15, 2);
            $table->date('jatuh_tempo');
            $table->enum('status_tagihan', ['pending', 'paid', 'overdue'])->default('pending');
            $table->timestamp('tgl_dibuat')->useCurrent();
            $table->timestamp('tgl_diperbarui')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};