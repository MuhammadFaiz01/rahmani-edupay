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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('nama_lengkap')->after('id');
            $table->enum('role', ['admin', 'ortu'])->after('password');
            $table->renameColumn('created_at', 'tgl_dibuat');
            $table->renameColumn('updated_at', 'tgl_diperbarui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama_lengkap', 'role']);
            $table->string('name')->after('id');
            $table->renameColumn('tgl_dibuat', 'created_at');
            $table->renameColumn('tgl_diperbarui', 'updated_at');
        });
    }
};