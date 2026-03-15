<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruiter_id',
        'title',
        'category',
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
}