<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DisputeTransitionRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['status'=>'required|in:assigned,investigating,awaiting_information,escalated,resolved,closed','notes'=>'required|string|min:3|max:4000']; } }
