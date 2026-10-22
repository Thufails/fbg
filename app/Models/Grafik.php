<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grafik extends Model
{
    use HasFactory;

    protected $table = 'grafik';
    protected $primaryKey = 'kd_grafik';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'title','urutan'
    ];
}