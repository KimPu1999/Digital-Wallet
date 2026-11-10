<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'payment_link_id' => ['nullable', 'uuid'],
            'amount_minor' => ['nullable', 'integer', 'min:1', 'max:9223372036854775807'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'order_reference' => ['required', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'success_url' => ['nullable', 'url', 'max:2048'],
            'cancel_url' => ['nullable', 'url', 'max:2048'],
            'allowed_payment_methods' => ['sometimes', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['string', 'in:wallet,card,bank_transfer'],
        ];
    }
}
