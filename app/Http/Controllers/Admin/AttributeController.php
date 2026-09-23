<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    private function checkPermission(Request $request): void
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
    }

    public function index(Request $request): View
    {
        $this->checkPermission($request);

        $attributes = Attribute::with('values')->orderBy('name')->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission($request);

        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:attributes,name']]);
        Attribute::create(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return back()->with('order_success', 'ویژگی ایجاد شد.');
    }

    public function storeValue(Request $request, Attribute $attribute): RedirectResponse
    {
        $this->checkPermission($request);

        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:100',
                // بخش ۳۴: پیام خطای فارسی تمیز به‌جای کرش خام دیتابیس روی Unique Index
                Rule::unique('attribute_values', 'value')->where('attribute_id', $attribute->id),
            ],
        ], [
            'value.unique' => 'این مقدار قبلاً برای همین ویژگی ثبت شده است.',
        ]);

        $attribute->values()->create(['value' => $data['value'], 'slug' => Str::slug($data['value'])]);

        return back()->with('order_success', 'مقدار جدید اضافه شد.');
    }

    public function destroyValue(Request $request, AttributeValue $value): RedirectResponse
    {
        $this->checkPermission($request);

        $value->delete();

        return back()->with('order_success', 'مقدار حذف شد.');
    }
}
