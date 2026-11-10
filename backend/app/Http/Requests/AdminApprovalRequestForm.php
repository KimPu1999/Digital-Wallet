<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminApprovalRequestForm extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['action'=>['required','string','max:120'],'resource_type'=>['nullable','string','max:160'],'resource_id'=>['nullable','uuid'],'reason'=>['required','string','max:2000'],'required_decisions'=>['sometimes','integer','in:1,2']]; } }
