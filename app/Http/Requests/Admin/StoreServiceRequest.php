<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:services,slug'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'mode' => ['required', Rule::in(['mock', 'proxy'])],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
        ];
    }
}
