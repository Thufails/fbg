<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $table = 'laporan';
    protected $primaryKey = 'kd_laporan';
    public $incrementing = true;
    protected $keyType = 'int';
    
    protected $fillable = [ 
        'tanggal',
        'tanggal_awal',
        'tanggal_akhir',
        'kd_user',
        'keterangan',
        'status',
        'path',
        'path_penjualan',
        'created_at',
        'updated_at',
        'path_pdf',
        'pdf_updated_at'
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'kd_user', 'id');
    }
}