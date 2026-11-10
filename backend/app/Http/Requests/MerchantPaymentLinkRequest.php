<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantPaymentLinkRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'amount_minor' => ['nullable', 'integer', 'min:1', 'max:9223372036854775807'],
            'currency_code' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'single_use' => ['sometimes', 'boolean'],
        ];
    }
}
