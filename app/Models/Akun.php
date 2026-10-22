<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Akun extends Model
{
    public $timestamps = false; // Nonaktifkan timestamps otomatis
    
    protected $table = 'akun';
    protected $primaryKey = 'kd_akun';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'kd_akun', 'nama_akun', 'induk', 
        'keterangan', 'urutan', 'operasi'
    ];
}