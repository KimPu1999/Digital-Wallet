<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentIntentRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'wallet_currency_account_id' => ['required', 'uuid'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'payment_method' => ['required', 'string', 'in:card,bank_transfer'],
            'provider_scenario' => ['nullable', 'string', 'in:processing,failed,unknown,timeout'],
            'challenge_id' => ['required_if:operation,withdrawal', 'uuid'],
            'challenge_code' => ['required_if:operation,withdrawal', 'string', 'max:64'],
        ];
    }
}
