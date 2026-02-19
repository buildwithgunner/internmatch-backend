<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'student_id',
        'application_id',
        'scheduled_at',
        'status',
        'type',
        'notes',
        'meeting_link',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
