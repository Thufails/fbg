<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KonversiTanggal extends Model
{
    use HasFactory;

    protected $table = 'konversi_tanggal';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    
    protected $fillable = [
        'tanggal',
        'tanggal_h',
        'hari_h',
        'bulan_h',
        'tahun_h',        
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tanggal' => 'date'
    ];
}
