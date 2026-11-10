<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminComplianceDecisionRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['status'=>['required','in:approved,rejected,manual_review'],'reason'=>['required','string','max:2000'],'challenge_id'=>['required','uuid'],'challenge_code'=>['required','string','max:64']]; } }
