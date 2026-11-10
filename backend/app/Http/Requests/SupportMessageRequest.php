<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupportMessageRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['body'=>'required|string|min:1|max:8000','client_message_key'=>'nullable|string|max:180','visibility'=>'nullable|in:customer,internal']; } }
