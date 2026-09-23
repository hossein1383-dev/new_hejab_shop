<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('coupons.manage') ?? false;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:coupons,code,' . $couponId],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'این کد تخفیف قبلاً استفاده شده است.',
            'value.required' => 'مقدار تخفیف (درصد یا مبلغ) الزامی است.',
            'ends_at.after_or_equal' => 'تاریخ پایان باید بعد از تاریخ شروع باشد.',
        ];
    }
}
