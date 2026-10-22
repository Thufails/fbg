<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';
    protected $primaryKey = 'kd_penjualan';
    public $timestamps = true;

    protected $fillable = [
        'tanggal',
        'kd_produk',
        'kd_entitas', // Tambahkan ini
        'harga',
        'jumlah',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'harga' => 'integer',
        'jumlah' => 'integer',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'kd_produk', 'kd_produk');
    }

    // Tambahkan relasi ke entitas
    public function entitas()
    {
        return $this->belongsTo(Entitas::class, 'kd_entitas', 'kd_entitas');
    }
}