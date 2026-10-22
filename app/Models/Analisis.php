<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analisis extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisis';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'kd_analisis';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kd_analisis',
        'kd_laporan',
        'kd_user',
        'uraian',
        'bulan',
        'tahun',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the laporan associated with the analisis.
     */
    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'kd_laporan', 'kd_laporan');
    }

    /**
     * Get the user who created the analisis.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'kd_user', 'id');
    }

    /**
     * Get the Indonesian month name.
     *
     * @return string
     */
    public function getBulanIndonesiaAttribute()
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $bulan[$this->bulan] ?? 'Tidak Valid';
    }
}