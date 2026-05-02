<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Internship;
use App\Models\Document;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'internship_id',
        'cover_letter_text',
        'portfolio_url',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'application_document')
                    ->withTimestamps();
    }

    // ── Ownership Scopes ─────────────────────────────────────────────────────

    /** Scope to applications submitted by a specific student. */
    public function scopeOwnedByStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /** Scope to applications for a specific recruiter's internships. */
    public function scopeOwnedByRecruiter(Builder $query, int $recruiterId): Builder
    {
        return $query->whereHas('internship', fn (Builder $q) => $q->where('recruiter_id', $recruiterId));
    }

    /** Scope to applications for internships belonging to a company. */
    public function scopeOwnedByCompany(Builder $query, \App\Models\Company $company): Builder
    {
        return $query->whereHas('internship.recruiter', fn (Builder $q) => $q->where('company_id', $company->id));
    }
}