<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class MfaCodeRequest extends FormRequest { public function authorize(): bool { return $this->user() !== null; } public function rules(): array { return ['code'=>['required','string','regex:/^\d{6}$/']]; } }
