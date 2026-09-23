<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // هردو اختیاری‌اند؛ الزامِ «حداقل یکی» در withValidator چک می‌شود
            // چون به رکورد فعلی (در Update) هم نیاز دارد، نه فقط به Request.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_mobile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'position' => ['required', 'in:home_hero'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /** حداقل یکی از دو تصویر باید در نهایت وجود داشته باشد — مهم نیست کدام. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $banner = $this->route('banner');

            $willHaveDesktop = $this->hasFile('image') || $banner?->image;
            $willHaveMobile = $this->hasFile('image_mobile') || $banner?->image_mobile;

            if (! $willHaveDesktop && ! $willHaveMobile) {
                $validator->errors()->add('image', 'حداقل یکی از تصویر دسکتاپ یا موبایل را انتخاب کنید.');
            }
        });
    }
}
