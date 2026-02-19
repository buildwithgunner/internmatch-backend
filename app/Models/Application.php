<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
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

   
}