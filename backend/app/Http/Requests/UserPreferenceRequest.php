<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPreferenceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'default_currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'preferred_wallet_id' => ['nullable', 'uuid'],
            'balance_visible' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'in:en,my'],
            'theme' => ['sometimes', 'in:light,dark,system'],
            'timezone' => ['sometimes', 'timezone'],
            'date_format' => ['sometimes', 'in:short,medium,long'],
            'number_format' => ['sometimes', 'in:locale,western'],
        ];
    }
}
