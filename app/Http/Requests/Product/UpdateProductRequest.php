<?php

namespace App\Http\Requests\Product;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $rules = parent::rules();
        // بخش ۳ (Soft Delete): محصولات حذف‌شده نباید در بررسی یکتایی حساب شوند
        $rules['slug'] = ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($productId)->whereNull('deleted_at')];
        $rules['sku'] = ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)->whereNull('deleted_at')];

        return $rules;
    }
}
