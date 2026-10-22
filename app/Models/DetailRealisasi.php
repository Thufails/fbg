<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRealisasi extends Model
{
    use HasFactory;

    protected $table = 'detail_realisasi';
    protected $primaryKey = 'kd_detail_realisasi';
    public $timestamps = true;

    protected $fillable = [
        'kd_target_detail',
        'tanggal',
        'nilai',
        'hari',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'tanggal' => 'integer', // Assuming tanggal is stored as an integer (e.g., YYYYMMDD)
        'nilai' => 'integer',
    ];

    public function targetDetail()
    {
        return $this->belongsTo(DetailTarget::class, 'kd_target_detail', 'kd_target_detail');
    }
}
