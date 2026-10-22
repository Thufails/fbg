<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    use HasFactory;

    // Menentukan nama tabel jika tidak sesuai dengan konvensi Laravel
    protected $table = 'issue';

    // Menentukan primary key
    protected $primaryKey = 'kd_issue';

    // Menentukan bahwa primary key adalah auto-increment
    public $incrementing = true;

    // Menentukan tipe data primary key
    protected $keyType = 'int'; // atau 'string' jika menggunakan string

    // Menentukan field yang dapat diisi massal
    protected $fillable = [
        'tanggal',
        'kd_user',
        'uraian',
        'bulan',
        'tahun',
        'status',
    ];
}
