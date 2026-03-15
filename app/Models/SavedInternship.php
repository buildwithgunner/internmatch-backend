<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedInternship extends Model
{
    protected $fillable = [
        'user_id',
        'internship_id',
    ];

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }
}
