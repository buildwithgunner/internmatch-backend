<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\NoEmoji;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Company;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255', new NoEmoji],
            'website'      => 'nullable|url|max:255',
            'description'  => ['nullable', 'string', new NoEmoji],
            'industry'     => ['nullable', 'string', 'max:100', new NoEmoji],
        ];
    }
}
