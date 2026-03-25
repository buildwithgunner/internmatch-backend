<?php

namespace App\Http\Requests\Recruiter;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NoEmoji;

class StoreInternshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller's instance check
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255', new NoEmoji],
            'category'          => 'nullable|string|max:255',
            'target_faculty'    => 'nullable|string|max:255',
            'target_department' => 'nullable|string|max:255',
            'description'       => 'required|string|min:50',
            'location'          => ['required', 'string', new NoEmoji],
            'type'              => 'required|in:Remote,Onsite,Hybrid',
            'duration'          => 'nullable|string',
            'stipend'           => 'nullable|string',
            'paid'              => 'boolean',
            'deadline'          => 'nullable|date|after:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'deadline.after' => 'The application deadline must be a future date.',
            'description.min' => 'Please provide a more detailed description (at least 50 characters).',
        ];
    }
}
