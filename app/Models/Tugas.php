<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    protected $table = 'tugas';
    protected $primaryKey = 'kd_tugas'; // Tambahkan ini
    public $incrementing = false; // Untuk BigQuery compatibility
    protected $keyType = 'string'; // Jika kd_tugas berupa string/UUID
    
    protected $fillable = [
        'kd_tugas', 'uraian', 'tanggal', 
        'bulan', 'tahun', 'status', 'kd_user'
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'kd_user', 'id');
    }
}