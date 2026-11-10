<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupportAssignmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['agent_id'=>'required|uuid']; } }
