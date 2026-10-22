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
        Schema::create('komplain', function (Blueprint $table) {
            $table->increments('kd_tugas'); // Menggunakan kd_issue sebagai primary key dan auto-increment
            $table->string('kd_user');
            $table->string('uraian');
            $table->string('bulan');
            $table->string('tahun');
            $table->integer('status');
            $table->string('sumber');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komplain');
    }
};
