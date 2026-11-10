<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ComplianceCaseRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['subject_type' => ['required','string','max:160'], 'subject_id' => ['required','uuid'], 'priority' => ['nullable','in:low,medium,high,critical'], 'case_type' => ['nullable','string','max:48'], 'reason' => ['required','string','min:5','max:255']]; } }
