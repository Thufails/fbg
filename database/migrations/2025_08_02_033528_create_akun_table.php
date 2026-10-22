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
        Schema::create('akun', function (Blueprint $table) {
            $table->string('kd_akun');
            $table->string('nama_akun');
            $table->string('induk');
            $table->string('keterangan');
            $table->integer('urutan');
            $table->integer('operasi');
            $table->timestamps(); // Menambahkan created_at dan updated_at
            $table->softDeletes(); // Opsional untuk soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun');
    }
};
