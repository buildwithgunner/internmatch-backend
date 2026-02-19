<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Internship;

class Company extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'company_name',
        'email',
        'password',
        'description',
        'website',
        'logo_path',
        'phone',
        'address',
        'is_verified',
        'otp',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_verified'       => 'boolean',
            'password'          => 'hashed',
        ];
    }

    public function internships()
    {
        return $this->hasMany(Internship::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}