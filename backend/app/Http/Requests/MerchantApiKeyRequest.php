<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantApiKeyRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'challenge_id' => ['required', 'uuid'],
            'challenge_code' => ['required', 'string', 'max:64'],
        ];
    }
}
