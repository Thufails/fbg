<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_perusahaan',
        'deskripsi',
        'alamat',
        'telepon',
        'email',
        'website',
        'logo',
    ];

    protected $table = 'profil_perusahaan';
}