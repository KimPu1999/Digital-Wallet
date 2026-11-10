<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdminRiskCaseRequest extends FormRequest { public function authorize(): bool { return auth()->check(); } public function rules(): array { return ['subject_type'=>['required','string','max:80'],'subject_id'=>['required','uuid'],'severity'=>['required','in:low,medium,high,critical'],'reason'=>['required','string','max:255']]; } }
