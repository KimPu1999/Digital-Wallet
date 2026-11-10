<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PlatformSettingRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['value'=>['required','array'],'approval_request_id'=>['nullable','uuid'],'challenge_id'=>['required','uuid'],'challenge_code'=>['required','string','max:64']]; } }
