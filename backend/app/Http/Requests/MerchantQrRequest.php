<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantQrRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'qr_type' => ['required', 'in:static,dynamic'],
            'amount_minor' => ['required_if:qr_type,dynamic', 'nullable', 'integer', 'min:1', 'max:9223372036854775807'],
            'currency_code' => ['required_if:qr_type,dynamic', 'nullable', 'string', 'size:3'],
            'order_reference' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
