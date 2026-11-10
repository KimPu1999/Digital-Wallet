<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class BeneficiaryRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['display_name'=>['required','string','max:120'],'beneficiary_type'=>['required','in:internal_wallet,bank_account,payout_provider'],'country_code'=>['nullable','string','size:2'],'currency_code'=>['nullable','string','size:3'],'account_details'=>['required','array','max:20'],'account_details.*'=>['nullable','string','max:255']]; } }
