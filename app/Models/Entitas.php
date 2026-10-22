<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitas extends Model
{
    public $timestamps = false; // Nonaktifkan timestamps
    
    protected $table = 'entitas';
    protected $primaryKey = 'kd_entitas';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'kd_entitas', 'nama_entitas', 'induk', 'keterangan'
    ];
}