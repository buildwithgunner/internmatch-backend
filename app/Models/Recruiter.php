<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Internship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Recruiter extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'sector',
        'phone',
        'role',
        'email_verified_at',
        'company_id',
        'position',
        'country',
        'bio',
        'linkedin',
        'website',
        'tangible_document',
        'trust_score',
        'reports_count',
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
            'is_banned'         => 'boolean',
            'password'          => 'hashed',
            'otp_expires_at'    => 'datetime',
        ];
    }

    protected $appends = ['trust_level'];

    public function getTrustLevelAttribute()
    {
        $score = $this->trust_score;
        if ($score >= 80) return 'Trusted Recruiter';
        if ($score >= 60) return 'Verified';
        if ($score >= 40) return 'Moderate';
        return 'Risky';
    }

    public function updateTrustScore()
    {
        $score = 0;

        if ($this->email_verified_at) $score += 20;
        if ($this->phone) $score += 20;
        if ($this->linkedin) $score += 10;
        if ($this->company_id) $score += 15;

        // Ensure is_verified is strictly based on having BOTH
        $this->is_verified = (!empty($this->website) && !empty($this->tangible_document));

        if ($this->is_verified) $score += 25;

        // Apply penalty for reports
        $score -= ($this->reports_count * 20);

        // Score should not be less than 0
        $this->trust_score = max(0, $score);
        
        // Auto-ban / auto-flag logic placeholder if score < 20
        // e.g., if ($this->trust_score < 20) $this->is_banned = true;

        $this->save();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function internships()
    {
        return $this->hasMany(Internship::class);
    }
}
