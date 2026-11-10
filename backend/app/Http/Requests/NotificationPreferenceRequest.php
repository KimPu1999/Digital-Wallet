<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class NotificationPreferenceRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['transaction_email'=>'sometimes|boolean','transaction_sms'=>'sometimes|boolean','transaction_push'=>'sometimes|boolean','support_email'=>'sometimes|boolean','support_push'=>'sometimes|boolean','product_email'=>'sometimes|boolean','marketing_email'=>'sometimes|boolean','marketing_sms'=>'sometimes|boolean','marketing_push'=>'sometimes|boolean']; } }
