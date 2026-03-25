<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NoEmoji;

class StoreRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255', new NoEmoji],
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:student,company,admin,recruiter',
            'phone'    => 'nullable|string|max:20',
            // Recruiter specific fields
            'recruiter_type' => 'nullable|required_if:role,recruiter|in:independent,company',
            'company_name'   => ['nullable', 'required_if:recruiter_type,company', 'string', 'max:255', new NoEmoji],
            'sector'         => ['nullable', 'required_if:role,recruiter', 'string', 'max:255', new NoEmoji],
            'position'       => ['nullable', 'required_if:recruiter_type,company', 'string', 'max:255', new NoEmoji],
            'website'        => 'nullable|string|max:255',
            'referral_code'  => 'nullable|string|max:10',
            'country'        => 'nullable|string|max:255',
            'captcha_answer' => 'required|string',
            'captcha_key'    => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required_if' => 'Company name is required for company recruiters.',
            'sector.required_if' => 'The industry/sector is required.',
            'captcha_answer.required' => 'Please solve the security challenge.',
        ];
    }
}
