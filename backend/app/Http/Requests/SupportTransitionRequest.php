<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupportTransitionRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['status'=>'required|in:assigned,in_progress,awaiting_customer,escalated,resolved,closed,reopened','notes'=>'nullable|string|max:4000']; } }
