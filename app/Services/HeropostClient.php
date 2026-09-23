<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * کلاینت وب‌سرویس هیروپست — بخش ۱۵. طبق مستندات API آنها (Login با
 * Bearer Token، سپس فراخوانی وب‌سرویس‌های /post/...).
 *
 * ⚠️ فرض مستند (بخش ۴۶): مدت اعتبار Token در مستندات هیروپست ذکر نشده؛
 * برای احتیاط ۴۵ دقیقه Cache می‌شود و در صورت خطای ۴۰۱، یک‌بار خودکار
 * دوباره لاگین و تلاش می‌شود.
 */
class HeropostClient
{
    private const TOKEN_CACHE_KEY = 'heropost.token';

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly string $securityCode,
    ) {
    }

    private function login(): string
    {
        $response = Http::asJson()->withOptions(['verify' => false])->timeout(30)->post("{$this->baseUrl}/login", [
            'username' => $this->username,
            'password' => $this->password,
            'security_code' => $this->securityCode,
        ]);

        $data = $response->json() ?? [];
        $token = $data['data']['token'] ?? null;

        if (! $token) {
            Log::error('Heropost login failed', ['response' => $data]);
            throw new \RuntimeException('اتصال به هیروپست ممکن نشد (خطای ورود).');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes(45));

        return $token;
    }

    private function token(): string
    {
        return Cache::get(self::TOKEN_CACHE_KEY) ?? $this->login();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        $makeRequest = fn (string $token) => Http::asJson()
            ->withToken($token)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->{$method}("{$this->baseUrl}{$path}", $payload);

        $response = $makeRequest($this->token());

        // Token منقضی‌شده — یک‌بار با لاگین تازه دوباره تلاش می‌شود
        if ($response->status() === 401) {
            $response = $makeRequest($this->login());
        }

        $data = $response->json() ?? [];

        if (! $response->successful() || isset($data['error'])) {
            Log::error('Heropost API request failed', ['path' => $path, 'response' => $data]);

            // ⚠️ هیروپست گاهی error را رشته و گاهی آرایه‌ای از پیام‌ها
            // برمی‌گرداند — RuntimeException فقط رشته قبول می‌کند.
            $message = $data['error'] ?? 'خطا در ارتباط با سرویس هیروپست.';
            $message = is_array($message) ? implode('، ', $message) : $message;

            throw new \RuntimeException($message);
        }

        return $data['data'] ?? [];
    }

    /** @return array<int, array{id:int, name:string}> */
    public function getCities(): array
    {
        return $this->request('get', '/post/cities');
    }

    /** شهرهای یک استان مشخص — برای تایید نگاشت شناسه/نام استان استفاده می‌شود. */
    public function getCitiesByProvince(int $provinceCode): array
    {
        return $this->request('get', "/post/cities/province/{$provinceCode}");
    }

    /** لیست انواع سرویس پستی (مثلاً پیشتاز/سفارشی/...) — طبق مستندات کامل بخش ۵۲. */
    public function getServiceTypes(): array
    {
        return $this->request('get', '/post/servicetype');
    }

    public function getBoxSizes(): array
    {
        return $this->request('get', '/post/boxsize');
    }

    public function getParcelCategories(): array
    {
        return $this->request('get', '/post/parcelcategories');
    }

    public function getPacketTypes(): array
    {
        return $this->request('get', '/post/packettype');
    }

    /**
     * استعلام قیمت پستی — دقیقاً همان فیلدهایی که در مستندات API الزامی است.
     * @return array{TotalPrice:int, EcommercePrice:int, Tax:int}
     */
    public function calculatePrice(array $payload): array
    {
        return $this->request('post', '/post/parcel/price', $payload);
    }

    /**
     * ثبت واقعی مرسوله — بخش ۵۲. کد رهگیری/شناسه مرسوله را برمی‌گرداند.
     */
    public function createParcel(array $payload): array
    {
        return $this->request('post', '/post/parcel/insert', $payload);
    }

    /**
     * لغو مرسوله ثبت‌شده — بخش ۵۲. طبق مستندات کامل، فیلد "barcode" است
     * (نه ParcelID که قبلاً حدس زده بودم).
     */
    public function cancelParcel(string $parcelCode): array
    {
        return $this->request('post', '/post/parcel/cancel', ['barcode' => $parcelCode]);
    }
}
