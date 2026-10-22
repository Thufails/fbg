<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $table = 'target'; // Explicitly set table name   
    protected $primaryKey = 'kd_target';
    public $incrementing = true; // Since it's AUTO_INCREMENT
    protected $keyType = 'int'; // Primary key is integer
    
    protected $fillable = [
        'kd_entitas', 
        'kd_akun', 
        'tanggal', 
        'nilai',
        'hari',
        'bulan',
        'tahun',
        'created_at',
        'updated_at'
    ];

    // If you need to cast any attributes
    protected $casts = [
        'tanggal' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'nilai' => 'integer'
    ];

    // Relationships if needed
    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'kd_entitas', 'kd_entitas');
    }

    public function akun()
    {
        return $this->belongsTo(Akun::class, 'kd_akun', 'kd_akun');
    }
}