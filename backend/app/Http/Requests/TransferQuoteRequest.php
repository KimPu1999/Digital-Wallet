<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class TransferQuoteRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['source_wallet_currency_account_id'=>['required','uuid'],'beneficiary_id'=>['required','uuid'],'beneficiary_account_id'=>['required','uuid'],'source_amount_minor'=>['required','integer','min:1','max:9223372036854775807']]; } }
