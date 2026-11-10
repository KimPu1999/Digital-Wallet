<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantRefundRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'payment_intent_id' => ['required', 'uuid'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
