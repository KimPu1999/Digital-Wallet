<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivacyRequestRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'in:access,correction,restriction,export,deletion'],
            'scope_summary' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
