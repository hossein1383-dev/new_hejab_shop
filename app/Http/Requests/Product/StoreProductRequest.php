<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // بخش ۳ (Soft Delete): یک محصول حذف‌شده نباید مانع استفاده دوباره از همان Slug/SKU شود
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->whereNull('deleted_at')],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->whereNull('deleted_at')],
            'barcode' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],

            // قیمت‌ها به کوچک‌ترین واحد پول (ریال) و به‌صورت عدد صحیح — بدون Floating Point (بخش ۳۵)
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'gte:price'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:100'],

            'status' => ['required', 'in:draft,active,inactive'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'is_bestseller' => ['sometimes', 'boolean'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'این SKU قبلاً ثبت شده است.',
            'compare_price.gte' => 'قیمت قبل از تخفیف باید بزرگتر یا مساوی قیمت فعلی باشد.',
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }
}
