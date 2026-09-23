<?php

namespace App\Http\Requests\Cart;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cart برای Guest هم مجاز است (بخش ۹)
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * اگر محصول Variant (رنگ/سایز) دارد، انتخاب یکی از آنها الزامی است — تا
     * ادمین همیشه بداند دقیقاً کدام ترکیب باید ارسال شود (نه فقط از UI،
     * بلکه حتی اگر کسی مستقیم API را صدا بزند).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('product_id') || $this->filled('product_variant_id')) {
                return;
            }

            $product = Product::find($this->input('product_id'));
            if ($product && $product->variants()->exists()) {
                $validator->errors()->add('product_variant_id', 'برای این محصول باید یکی از گزینه‌ها (مثلاً رنگ/سایز) را انتخاب کنید.');
            }
        });
    }
}
