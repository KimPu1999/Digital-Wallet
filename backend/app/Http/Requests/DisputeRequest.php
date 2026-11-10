<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DisputeRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['classification'=>'required|string|max:64','reason'=>'required|string|min:5|max:255','payment_intent_id'=>'nullable|uuid','priority'=>'nullable|in:low,normal,high,urgent','support_ticket_id'=>'nullable|uuid']; } }
