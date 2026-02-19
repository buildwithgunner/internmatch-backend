<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'bio',
        'skills',
        'linkedin',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==================== ROLE HELPER METHODS ====================
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isCompany(): bool
    {
        return $this->role === 'company';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the student profile is complete.
     */
    public function isProfileComplete(): bool
    {
        if (!$this->isStudent()) {
            return true;
        }

        // Basic fields
        if (empty($this->bio) || empty($this->skills) || empty($this->phone)) {
            return false;
        }

        // Required documents: resume, university_certificate, passport_photo
        $requiredTypes = ['resume', 'university_certificate', 'passport_photo'];
        $uploadedTypes = $this->documents()->pluck('type')->toArray();

        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploadedTypes)) {
                return false;
            }
        }

        return true;
    }
    // ===========================================================

    // Relationship to documents
    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class);
    }
    // Relationship to applications (as student)
    public function applications()
    {
        return $this->hasMany(Application::class, 'student_id');
    }

    // Relationship to interviews (as student)
    public function interviews()
    {
        return $this->hasMany(Interview::class, 'student_id');
    }
}