<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // اگر هنوز لیست شهرهای هیروپست همگام‌سازی نشده (heropost:sync-cities
        // اجرا نشده)، فرم قدیمی (متن آزاد) باید همچنان کار کند — وگرنه با
        // فعال‌سازی این ویژگی، کسی که هنوز اطلاعات هیروپست را نگرفته اصلاً
        // نمی‌تواند آدرس اضافه کند.
        $citiesSynced = \App\Models\HeropostCity::query()->exists();

        return [
            'title' => ['nullable', 'string', 'max:100'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'heropost_city_id' => [$citiesSynced ? 'required' : 'nullable', 'integer', 'exists:heropost_cities,heropost_city_id'],
            'address_line' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
