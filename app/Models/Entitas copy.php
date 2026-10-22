<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitas extends Model
{
        //
    protected $table = 'entitas';
    protected $fillable = ['kd_entitas','nama_entitas','induk','keterangan'];

    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kd_entitas';
}
