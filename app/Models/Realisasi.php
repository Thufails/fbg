<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Realisasi extends Model
{
    use HasFactory;

    protected $table = 'realisasi';
    protected $primaryKey = 'kd_realisasi';
    public $incrementing = true;
    protected $keyType = 'integer';

    protected $fillable = [
        'kd_target',
        'tanggal',
        'nilai',
        'hari',
        'bulan',
        'tahun',
        'keterangan',
        'created_at',
        'updated_at'
    ];

    public function target()
    {
        return $this->belongsTo(Target::class, 'kd_target', 'kd_target');
    }
}