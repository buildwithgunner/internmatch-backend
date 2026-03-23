<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesCategorization;

class StudentProfile extends Model
{
    use HasFactory, HandlesCategorization;

    protected $fillable = [
        'user_id',
        'university',
        'faculty',
        'department',
        'level',
        'graduation_year',
        'bio',
        'skills',
        'resume',
        'country',
        'state',
        'city',
        'portfolio_url',
        'github_url',
        'linkedin_url',
        'website_url',
        'preferred_role',
        'internship_type',
        'availability',
        'interests',
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
