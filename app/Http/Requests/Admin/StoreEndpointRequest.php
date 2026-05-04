<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'method' => ['required', Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'ANY'])],
            'path' => ['required', 'string', 'max:500', 'starts_with:/'],
            'mode_override' => ['nullable', Rule::in(['mock', 'proxy'])],
            'proxy_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
