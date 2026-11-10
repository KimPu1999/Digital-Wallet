<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class WalletPreferenceRequest extends FormRequest { public function authorize(): bool { return $this->user() !== null; } public function rules(): array { return ['preferred_currency_code'=>['nullable','string','size:3','alpha'],'locale'=>['nullable','in:en,mm']]; } }
