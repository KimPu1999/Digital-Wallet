<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class PhaseTwoRegisterRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['name'=>['required','string','max:120'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:12','confirmed'],'country_code'=>['required','string','size:2','alpha'],'phone_e164'=>['required','regex:/^\+[1-9]\d{7,14}$/']]; } }
