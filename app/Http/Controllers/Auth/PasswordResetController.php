<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * فراموشی/بازیابی رمز عبور — بخش ۴ (این بخش در فاز ۱ جا مانده بود و اینجا
 * تکمیل می‌شود). از Password Broker استاندارد خود Laravel استفاده می‌شود
 * که همان جدول password_reset_tokens فاز ۱ را به‌کار می‌برد.
 */
class PasswordResetController extends Controller
{
    /** ارسال لینک بازیابی رمز عبور (بخش ۴). */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Rate Limiting در برابر سوءاستفاده از این Endpoint برای Spam ایمیل (بخش ۳۴)
        $status = Password::sendResetLink($request->only('email'));

        // همیشه پیام یکسان برگردانده می‌شود تا مشخص نشود ایمیلی در سیستم
        // ثبت شده یا نه (بخش ۳۴: جلوگیری از افشای اطلاعات کاربران).
        return response()->json([
            'success' => true,
            'message' => 'اگر این ایمیل در سیستم ثبت شده باشد، لینک بازیابی رمز عبور برایش ارسال شد.',
            'data' => [],
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'لینک بازیابی نامعتبر یا منقضی شده است. دوباره درخواست دهید.',
                'errors' => [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.',
            'data' => [],
        ]);
    }
}
