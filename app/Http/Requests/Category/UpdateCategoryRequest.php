<?php

namespace App\Http\Requests\Category;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['slug'] = ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($this->route('category')?->id)->whereNull('deleted_at')];

        return $rules;
    }
}
