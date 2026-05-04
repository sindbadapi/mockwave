<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMockResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status_code' => ['sometimes', 'integer', 'min:100', 'max:599'],
            'body' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'delay_ms' => ['sometimes', 'integer', 'min:0', 'max:30000'],
        ];
    }
}
