<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'level', // Pastikan ini ada
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Tambahkan method untuk mengecek level
    public function isAdmin()
    {
        return $this->level === 'admin';
    }

    public function isUser()
    {
        return $this->level === 'user';
    }

    public function isGuest()
    {
        return $this->level === 'guest';
    }

    public function isAnalis()
    {
        return $this->level === 'analis';
    }
}