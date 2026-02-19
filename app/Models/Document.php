<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper to get URL (assuming stored in storage/public)
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}