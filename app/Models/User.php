<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'profile_photo',
        'is_banned',
        'is_verified',
        'otp',
        'otp_expires_at',
        'referred_by_ambassador_id',
        'referral_rewarded',
    ];

    /**
     * Relationship to StudentProfile
     */
    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

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
            'is_verified' => 'boolean',
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

        $profile = $this->profile;

        if (!$profile) {
            return false;
        }

        // Required Profile fields: university, faculty, department, level, graduation_year, country, state, city
        $requiredProfileFields = ['university', 'faculty', 'department', 'level', 'graduation_year', 'country', 'state', 'city'];
        
        foreach ($requiredProfileFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }

        // Required preferences
        if (empty($profile->preferred_role) || empty($profile->internship_type) || empty($profile->availability)) {
            return false;
        }

        // Required documents: resume
        $hasResume = $this->documents()->where('type', 'resume')->exists();
        if (!$hasResume) {
            return false;
        }

        return true;
    }

    /**
     * Calculate profile strength and tasks.
     */
    public function calculateProfileStrength(): array
    {
        if (!$this->isStudent()) {
            return ['percentage' => 100, 'tasks' => []];
        }

        $profile = $this->profile;

        // Check social links count
        $linkCount = 0;
        if (!empty($profile?->linkedin_url)) $linkCount++;
        if (!empty($profile?->portfolio_url)) $linkCount++;
        if (!empty($profile?->github_url)) $linkCount++;
        if (!empty($profile?->website_url)) $linkCount++;

        $tasks = [
            ['label' => 'Identity (Name, Email, Phone)', 'completed' => !empty($this->name) && !empty($this->email) && !empty($this->phone), 'points' => 10],
            ['label' => 'University & Faculty', 'completed' => !empty($profile?->university) && !empty($profile?->faculty) && !empty($profile?->graduation_year), 'points' => 15],
            ['label' => 'Department & Level', 'completed' => !empty($profile?->department) && !empty($profile?->level), 'points' => 10],
            ['label' => 'Skills', 'completed' => !empty($profile?->skills), 'points' => 10],
            ['label' => 'Bio', 'completed' => !empty($profile?->bio), 'points' => 10],
            ['label' => 'Upload Resume', 'completed' => $this->documents()->where('type', 'resume')->exists(), 'points' => 15],
            ['label' => 'Full Location (City/State/Country)', 'completed' => !empty($profile?->country) && !empty($profile?->state) && !empty($profile?->city), 'points' => 10],
            ['label' => 'Internship Preferences', 'completed' => !empty($profile?->preferred_role) && !empty($profile?->internship_type) && !empty($profile?->availability), 'points' => 15],
            ['label' => 'Social Links (At least 2)', 'completed' => $linkCount >= 2, 'points' => 5],
        ];

        $percentage = collect($tasks)->where('completed', true)->sum('points');

        return [
            'percentage' => $percentage,
            'tasks' => $tasks
        ];
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

    /**
     * Relationship to SavedCandidates (for N+1 optimization)
     */
    public function savedByRecruiters()
    {
        return $this->hasMany(SavedCandidate::class, 'student_id');
    }
}