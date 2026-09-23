<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * ثبت‌نام/ورود کاملاً با OTP جایگزین شد (OtpService)؛ اینجا فقط Logout مانده.
 */
class AuthService
{
    public function logout(): void
    {
        Auth::logout();
    }
}
