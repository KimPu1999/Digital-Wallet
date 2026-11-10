<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class WalletCreationRequest extends FormRequest { public function authorize(): bool { return $this->user() !== null; } public function rules(): array { return ['currency_code'=>['required','string','size:3','alpha'],'nickname'=>['nullable','string','max:80']]; } }
