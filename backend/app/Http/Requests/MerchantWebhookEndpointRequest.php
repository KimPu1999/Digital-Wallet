<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'max:80'],
            'challenge_id' => ['required', 'uuid'],
            'challenge_code' => ['required', 'string', 'max:64'],
        ];
    }
}
