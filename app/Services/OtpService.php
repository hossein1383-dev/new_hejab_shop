<?php

namespace App\Services;

use App\Contracts\SmsGatewayContract;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * احراز هویت با شماره‌تلفن + کد یک‌بارمصرف — جایگزین کامل ایمیل/رمز عبور
 * (تصمیم صریح کارفرما). بخش ۳۴: کد هرگز خام ذخیره نمی‌شود، تلاش‌های
 * ناموفق محدود می‌شوند، و کد بعد از مصرف/انقضا بی‌اثر است.
 */
class OtpService
{
    private const CODE_LENGTH = 5;
    private const EXPIRY_MINUTES = 2;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsGatewayContract $smsGateway)
    {
    }

    /** ساخت و ارسال کد OTP جدید برای یک شماره تلفن. */
    public function requestCode(string $phone): void
    {
        $code = (string) random_int(
            (int) str_pad('1', self::CODE_LENGTH, '0'),
            (int) str_pad('', self::CODE_LENGTH, '9')
        );

        OtpCode::create([
            'phone' => $phone,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->smsGateway->sendOtp($phone, $code);
    }

    /**
     * بررسی کد وارد‌شده. اگر درست بود، کاربر متناظر با این شماره را
     * برمی‌گرداند (اگر برای اولین‌بار است، کاربر جدید ساخته می‌شود).
     *
     * @throws \DomainException
     */
    public function verifyCode(string $phone, string $code): User
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw new \DomainException('کدی برای این شماره درخواست نشده است.');
        }

        if ($otp->isExpired()) {
            throw new \DomainException('کد منقضی شده است. دوباره درخواست دهید.');
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw new \DomainException('تعداد تلاش‌های مجاز تمام شده. کد جدید درخواست دهید.');
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');
            throw new \DomainException('کد وارد‌شده صحیح نیست.');
        }

        $otp->update(['verified_at' => now()]);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'کاربر ' . Str::substr($phone, -4),
                'phone' => $phone,
                'phone_verified_at' => now(),
                'status' => 'active',
            ]);

            $customerRole = \App\Models\Role::where('slug', 'customer')->first();
            if ($customerRole) {
                $user->roles()->attach($customerRole->id);
            }
        } elseif (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        return $user;
    }
}
