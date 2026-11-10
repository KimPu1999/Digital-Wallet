<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ComplianceCaseEventRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['status' => ['required','in:assigned,investigating,awaiting_information,escalated,resolved,closed'], 'notes' => ['required','string','min:3','max:4000'], 'challenge_id' => ['required','uuid'], 'challenge_code' => ['required','string','size:6']]; } }
