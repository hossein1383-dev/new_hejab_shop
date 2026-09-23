<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فقط خروج از حساب اینجا مانده — ورود/ثبت‌نام کاملاً با OTP جایگزین شد
 * (بخش ۴، تصمیم صریح کارفرما) و در OtpAuthController مدیریت می‌شود.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد.',
            'data' => [],
        ]);
    }
}
