<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesCategorization;

class Internship extends Model
{
    use HasFactory, HandlesCategorization;

    protected $fillable = [
        'recruiter_id',
        'title',
        'category',
        'target_faculty',
        'target_department',
        'description',
        'location',
        'type',
        'duration',
        'stipend',
        'paid',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
            'deadline' => 'date',
        ];
    }

    // Relationships
    public function recruiter()
    {
        return $this->belongsTo(Recruiter::class, 'recruiter_id');
    }
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function company()
    {
        return $this->hasOneThrough(
            Company::class,
            Recruiter::class,
            'id',           // Foreign key on recruiters table...
            'id',           // Foreign key on companies table...
            'recruiter_id', // Local key on internships table...
            'company_id'    // Local key on recruiters table...
        );
    }
    public function savedByUsers()
    {
        return $this->hasMany(SavedInternship::class);
    }
}