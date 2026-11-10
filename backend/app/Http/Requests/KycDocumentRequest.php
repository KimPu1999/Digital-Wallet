<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class KycDocumentRequest extends FormRequest { public function authorize(): bool { return $this->user() !== null; } public function rules(): array { return ['document_type'=>['required','string','max:64'],'document'=>['required','file','mimes:pdf,jpg,jpeg,png','max:10240']]; } }
