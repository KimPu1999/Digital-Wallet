<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletCheckoutRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'source_wallet_currency_account_id' => ['required', 'uuid'],
            'challenge_id' => ['required', 'uuid'],
            'challenge_code' => ['required', 'string', 'max:64'],
        ];
    }
}
