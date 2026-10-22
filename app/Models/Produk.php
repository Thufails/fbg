<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    protected $fillable = ['kd_produk','nama_produk','harga','keterangan'];

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kd_produk';
}
