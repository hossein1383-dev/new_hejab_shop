<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('inventory.view') || $request->user()->hasPermission('inventory.manage'), 403);

        $products = Product::query()
            ->with(['inventory', 'variants.inventory'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%'))
            ->when($request->boolean('low_stock'), function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('inventory', fn ($iq) => $iq->where('quantity', '<=', 5))
                        ->orWhereHas('variants.inventory', fn ($iq) => $iq->where('quantity', '<=', 5));
                });
            })
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.index', compact('products'));
    }

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);

        $data = $request->validate([
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'type' => ['required', 'in:purchase,damage,return,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = ($data['product_variant_id'] ?? null) ? ProductVariant::find($data['product_variant_id']) : null;
        // damage باید موجودی را کم کند، بقیه اضافه می‌کنند (بخش ۸)
        $signedQuantity = $data['type'] === 'damage' ? -$data['quantity'] : $data['quantity'];

        try {
            $this->inventoryService->recordMovement(
                $product,
                $variant,
                $data['type'],
                $signedQuantity,
                null,
                $request->user()->id,
                $data['note'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->with('order_error', $e->getMessage());
        }

        return back()->with('order_success', 'موجودی به‌روزرسانی شد.');
    }
}
