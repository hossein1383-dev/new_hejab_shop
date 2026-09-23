<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('categories.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // بخش ۳ (Soft Delete): دسته‌بندی حذف‌شده نباید مانع استفاده دوباره از همان Slug شود
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->whereNull('deleted_at')],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    /** جلوگیری از قرار دادن Category به‌عنوان زیرمجموعه خودش (Circular Reference) */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('parent_id') && (int) $this->input('parent_id') === (int) $this->route('category')?->id) {
                $validator->errors()->add('parent_id', 'یک دسته‌بندی نمی‌تواند زیرمجموعه خودش باشد.');
            }
        });
    }
}
