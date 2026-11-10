<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupportAttachmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['attachment'=>'required|file|max:5120|mimes:pdf,png,jpg,jpeg,txt,csv']; } }
