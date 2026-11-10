<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminRestrictionRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['account_status'=>['required','in:active,suspended,restricted,closed'],'reason'=>['required','string','max:2000'],'approval_request_id'=>['required','uuid'],'challenge_id'=>['required','uuid'],'challenge_code'=>['required','string','max:64']]; } }
