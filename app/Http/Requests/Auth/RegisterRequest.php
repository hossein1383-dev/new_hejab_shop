<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Register همیشه برای Guest مجاز است
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.confirmed' => 'رمز عبور و تکرار آن یکسان نیستند.',
        ];
    }
}
