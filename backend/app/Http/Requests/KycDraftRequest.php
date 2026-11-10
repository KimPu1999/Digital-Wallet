<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class KycDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'legal_first_name' => ['nullable', 'string', 'max:120'],
            'legal_last_name' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'nationality' => ['nullable', 'string', 'size:2', 'alpha'],
            'residence_country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'address_line1' => ['nullable', 'string', 'max:180'],
            'address_line2' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:24'],
        ];
    }
}
