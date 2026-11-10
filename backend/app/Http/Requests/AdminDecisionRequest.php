<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminDecisionRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['decision'=>['required','in:approved,rejected'],'reason'=>['required','string','max:2000'],'challenge_id'=>['required','uuid'],'challenge_code'=>['required','string','max:64']]; } }
