<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilAnalisis extends Model
{
    use HasFactory;

    protected $table = 'hasil_analisis';
    protected $primaryKey = 'kd_hasil_analisis';
    public $timestamps = true;

    protected $fillable = [
        'kd_laporan',
        'kd_grafik',
        'title',
        'data',
        'analisis',
        'prompt'
    ];

    protected $casts = [
        'data' => 'array',
        'analisis' => 'array'
    ];

    // Jika ingin menggunakan kode sebagai primary key yang bukan auto-increment
    public $incrementing = true;
}