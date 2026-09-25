<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VariantController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    private function checkPermission(Request $request): void
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
    }

    public function index(Request $request, Product $product): View
    {
        $this->checkPermission($request);

        $product->load('variants.attributeValues.attribute', 'variants.inventory');
        $attributes = Attribute::with('values')->get();
        $totalAvailableQuantity = $this->inventoryService->totalAvailableQuantity($product);

        return view('admin.variants.index', compact('product', 'attributes', 'totalAvailableQuantity'));
    }

    /**
     * ساخت دسته‌جمعی Variant — بخش ۷: به‌جای ساختن هر ترکیب رنگ×سایز یکی‌یکی،
     * چند مقدار از هر ویژگی انتخاب می‌شود و همه ترکیب‌های ممکن (Cartesian
     * Product) به‌همراه موجودی اولیه یکسان، یک‌جا ساخته می‌شوند.
     */
    public function bulkStore(Request $request, Product $product): RedirectResponse
    {
        $this->checkPermission($request);

        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['array'],
            'values.*.*' => ['integer', 'exists:attribute_values,id'],
            'price' => ['nullable', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ], [
            'values.required' => 'باید حداقل از یک ویژگی، یک مقدار انتخاب کنید.',
        ]);

        // فقط ویژگی‌هایی که حداقل یک مقدار برایشان انتخاب شده وارد ترکیب می‌شوند
        $selectedGroups = array_values(array_filter($data['values'], fn ($ids) => ! empty($ids)));

        if (empty($selectedGroups)) {
            return back()->withErrors(['values' => 'باید حداقل از یک ویژگی، یک مقدار انتخاب کنید.'])->withInput();
        }

        // ساخت Cartesian Product همه ترکیب‌های ممکن
        $combinations = array_reduce($selectedGroups, function ($carry, $group) {
            $result = [];
            foreach ($carry as $combo) {
                foreach ($group as $valueId) {
                    $result[] = [...$combo, $valueId];
                }
            }
            return $result;
        }, [[]]);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($combinations, $product, $data, &$created, &$skipped) {
            foreach ($combinations as $valueIds) {
                $values = \App\Models\AttributeValue::whereIn('id', $valueIds)->get();
                $slugPart = $values->pluck('slug')->implode('-');
                $sku = Str::upper($product->sku . '-' . $slugPart);

                // اگر این ترکیب دقیق قبلاً ساخته شده، رد شود (Idempotent — بخش ۴۶)
                $alreadyExists = ProductVariant::where('product_id', $product->id)
                    ->whereHas('attributeValues', fn ($q) => $q->whereIn('attribute_value_id', $valueIds), '=', count($valueIds))
                    ->whereDoesntHave('attributeValues', fn ($q) => $q->whereNotIn('attribute_value_id', $valueIds))
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'price' => $data['price'] ?? null,
                    'compare_price' => $data['compare_price'] ?? null,
                    'status' => $data['status'],
                ]);

                $variant->attributeValues()->sync($valueIds);

                if (! empty($data['initial_stock'])) {
                    $this->inventoryService->recordMovement(
                        $product,
                        $variant,
                        'purchase',
                        $data['initial_stock'],
                        null,
                        request()->user()->id,
                        'موجودی اولیه هنگام ساخت دسته‌جمعی Variant'
                    );
                }

                $created++;
            }
        });

        $message = "{$created} Variant جدید ساخته شد.";
        if ($skipped > 0) {
            $message .= " ({$skipped} ترکیب تکراری رد شد.)";
        }

        return redirect()->route('admin.products.variants.index', $product)->with('order_success', $message);
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->checkPermission($request);

        // آزادسازی SKU تا بعداً قابل استفاده دوباره باشد
        $variant->update(['sku' => $variant->sku . '-deleted-' . $variant->id]);
        $variant->delete();

        return back()->with('order_success', 'Variant حذف شد.');
    }
}
