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
        Schema::create('entitas', function (Blueprint $table) {
            $table->string('kd_entitas', 5)->primary();
            $table->string('nama_entitas', 25);
            $table->string('induk', 5)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps(); // Opsional, sesuaikan dengan kebutuhan
            $table->softDeletes(); // Untuk kompatibilitas BigQuery
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entitas');
    }
};
