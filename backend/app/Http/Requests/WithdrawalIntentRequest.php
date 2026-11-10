<?php

namespace App\Http\Requests;

class WithdrawalIntentRequest extends PaymentIntentRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'challenge_id' => ['required', 'uuid'],
            'challenge_code' => ['required', 'string', 'max:64'],
        ];
    }
}
