<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ReportRunRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['report_type'=>['required','in:overview,trial-balance,profit-loss,balance-sheet,merchant-overview'], 'from'=>['nullable','date'], 'to'=>['nullable','date','after_or_equal:from'], 'currency'=>['nullable','string','size:3','in:USD,EUR,GBP,SGD,THB,MMK'], 'merchant_id'=>['nullable','uuid']]; } }
