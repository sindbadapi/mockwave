<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduledWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'target_url' => ['sometimes', 'url', 'max:500'],
            'method' => ['sometimes', Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])],
            'payload' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'cron_expression' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
