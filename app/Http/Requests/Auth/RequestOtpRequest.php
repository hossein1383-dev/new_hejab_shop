<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // فرمت شماره موبایل ایران؛ در صورت نیاز به کشورهای دیگر قابل تعمیم است
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'شماره موبایل معتبر نیست (مثال: 09123456789).'];
    }
}
