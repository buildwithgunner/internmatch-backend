<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cover_letter_text' => 'nullable|string|max:5000',
            'portfolio_url'     => 'nullable|url|max:255',
            'document_types'    => 'sometimes|array',
            'document_types.*'  => 'in:resume,cover_letter,student_id,transcript,primary_certificate,secondary_certificate,university_certificate,certificate,recommendation_letter,passport_photo',
        ];
    }
}
