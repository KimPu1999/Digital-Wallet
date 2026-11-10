<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminStepUpRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['challenge_id'=>['required','uuid'],'challenge_code'=>['required','string','max:64']]; } }
