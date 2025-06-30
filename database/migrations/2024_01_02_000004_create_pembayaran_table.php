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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_tagihan')->constrained('tagihan', 'id_tagihan')->onDelete('cascade');
            $table->string('midtrans_order_id')->nullable();
            $table->string('midtrans_trx_id')->nullable();
            $table->decimal('jml_dibayar', 15, 2);
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['pending', 'success', 'failed'])->default('pending');
            $table->string('bukti_pembayaran')->nullable();
            $table->timestamp('tgl_pembayaran')->nullable();
            $table->timestamp('tgl_dibuat')->useCurrent();
            $table->timestamp('tgl_diperbarui')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};