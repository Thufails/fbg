<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTarget extends Model
{
    use HasFactory;

    protected $table = 'detail_target';
    protected $primaryKey = 'kd_target_detail';
    public $timestamps = true;

    protected $fillable = [
        'tanggal',
        'kd_target',
        'kd_detail_akun',
        'nilai',
        'hari',
        'bulan',
        'tahun'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nilai' => 'decimal:2',
    ];

    public function detailAkun()
    {
        return $this->belongsTo(DetailAkun::class, 'kd_detail_akun', 'kd_detail_akun');
    }
}