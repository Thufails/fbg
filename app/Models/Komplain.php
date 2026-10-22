<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komplain extends Model
{
    protected $table = 'komplain'; // Explicitly set table name   
    protected $primaryKey = 'kd_komplain';
    public $incrementing = false;
    protected $keyType = 'string'; // or 'int' depending on your ID format
    
    protected $fillable = [
        'kd_komplain','kd_user', 'uraian', 'tanggal', 'bulan', 'tahun',
        'sumber', 'kategori', 'prioritas', 'status'
    ];

    // // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'kd_user', 'id');
    }
}