<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ExecuteTransferRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['quote_id'=>['required','uuid'],'challenge_id'=>['required','uuid'],'challenge_code'=>['required','digits:6'],'provider_scenario'=>['nullable','in:accepted,processing,failed,timeout,returned']]; } }
