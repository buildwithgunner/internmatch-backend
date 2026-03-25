<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NoEmoji;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            // Basic User Info
            'name'            => ['sometimes', 'string', 'max:255', new NoEmoji],
            'phone'           => 'nullable|string|max:20',
            
            // Academic Info
            'university'      => ['nullable', 'string', 'max:255', new NoEmoji],
            'faculty'         => ['nullable', 'string', 'max:255', new NoEmoji],
            'department'      => ['nullable', 'string', 'max:255', new NoEmoji],
            'level'           => 'nullable|string|max:50',
            'graduation_year' => 'nullable|integer',
            
            // Profile Details
            'bio'             => ['nullable', 'string', new NoEmoji],
            'skills'          => ['nullable', 'string', new NoEmoji],
            
            // Location
            'country'         => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            
            // Links
            'portfolio_url'   => ['nullable', 'url', 'max:255'],
            'github_url'      => ['nullable', 'url', 'max:255'],
            'linkedin_url'    => ['nullable', 'url', 'max:255'],
            'website_url'     => ['nullable', 'url', 'max:255'],

            // Internship Preferences
            'preferred_role'  => ['nullable', 'string', 'max:255', new NoEmoji],
            'internship_type' => 'nullable|in:Remote,Onsite,Hybrid',
            'availability'    => 'nullable|in:Full-time,Part-time',
            'interests'       => ['nullable', 'string', new NoEmoji],
        ];
    }
}
