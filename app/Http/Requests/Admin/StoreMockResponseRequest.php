<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMockResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'endpoint_id' => ['required', 'integer', 'exists:endpoints,id'],
            'status_code' => ['required', 'integer', 'min:100', 'max:599'],
            'body' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'delay_ms' => ['integer', 'min:0', 'max:30000'],
        ];
    }
}
