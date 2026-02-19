<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
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
   public function company()
{
    return $this->belongsTo(Company::class, 'company_id');
}

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}