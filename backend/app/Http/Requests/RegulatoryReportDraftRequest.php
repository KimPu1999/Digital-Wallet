<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RegulatoryReportDraftRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['report_type' => ['required','string','max:64'], 'jurisdiction' => ['nullable','string','max:8'], 'summary' => ['required','string','min:5','max:10000'], 'evidence_references' => ['nullable','array']]; } }
