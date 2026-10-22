<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAkun extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     * 
     * @var string
     */
    protected $table = 'detail_akun';

    /**
     * Kolom primary key yang digunakan.
     * 
     * @var string
     */
    protected $primaryKey = 'kd_detail_akun';

    /**
     * Indicates if the model's ID is auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe data primary key.
     * 
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'kd_detail_akun',
        'kd_akun',
        'nama_detail',
        'keterangan'
    ];

    /**
     * Kolom yang harus disembunyikan dari array atau JSON.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Kolom yang harus di-cast ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke model Akun (many-to-one)
     */
    public function akun()
    {
        return $this->belongsTo(Akun::class, 'kd_akun', 'kd_akun');
    }
}