<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruiter_id',
        'student_id',
        'notes',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function recruiter()
    {
        return $this->belongsTo(Recruiter::class);
    }
}
