<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Guest Checkout همیشه مجاز است (بخش ۲۹.۱)
    }

    public function rules(): array
    {
        $isGuest = ! $this->user();

        return [
            'address_id' => ['nullable', 'required_without:address', 'exists:addresses,id'],

            'address' => ['nullable', 'required_without:address_id', 'array'],
            'address.receiver_name' => ['required_with:address', 'string', 'max:255'],
            'address.phone' => ['required_with:address', 'string', 'max:20'],
            'address.province' => ['required_with:address', 'string', 'max:100'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.heropost_city_id' => [
                \App\Models\HeropostCity::query()->exists() ? 'required_with:address' : 'nullable',
                'integer', 'exists:heropost_cities,heropost_city_id',
            ],
            'address.address_line' => ['required_with:address', 'string', 'max:500'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],

            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_email' => [$isGuest ? 'required' : 'nullable', 'email', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],

            'shipping_method' => ['nullable', 'string', 'in:standard'],
            'service_type' => ['nullable', 'integer', 'in:1,5'], // بخش ۵۲: ۱=پیشتاز، ۳=ویژه
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'in:gateway,wallet'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required_without' => 'انتخاب یا وارد کردن آدرس ارسال الزامی است.',
            'guest_name.required' => 'برای ادامه به‌عنوان مهمان، نام الزامی است.',
        ];
    }
}
