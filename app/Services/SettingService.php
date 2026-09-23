<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * تنظیمات سایت با Cache (بخش ۳۳) — چون این مقادیر روی هر صفحه خوانده
 * می‌شوند و به‌ندرت تغییر می‌کنند.
 */
class SettingService
{
    private const CACHE_KEY = 'settings.all';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            return Setting::pluck('value', 'key')->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
