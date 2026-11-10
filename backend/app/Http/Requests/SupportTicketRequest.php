<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SupportTicketRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['category'=>'required|in:payment,settlement,refund,checkout,api,webhook,kyc,kyb,account_access,general','subject'=>'required|string|min:3|max:180','priority'=>'nullable|in:low,normal,high,urgent','message'=>'nullable|string|max:8000','client_message_key'=>'nullable|string|max:180','merchant_id'=>'nullable|uuid']; } }
