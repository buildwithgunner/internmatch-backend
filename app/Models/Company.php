<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Internship;

class Company extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;


    protected $fillable = [
        'company_name',
        'email',
        'password',
        'description',
        'website',
        'logo_path',
        'phone',
        'address',
        'country',
        'is_verified',
        'is_banned',
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

    public function recruiters()
    {
        return $this->hasMany(Recruiter::class);
    }

    public function internships()
    {
        return $this->hasManyThrough(Internship::class, Recruiter::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}