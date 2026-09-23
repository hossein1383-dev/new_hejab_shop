<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('reports.view'), 403);

        // فروش ۷ روز اخیر (بخش ۱۹: Sales Chart) — بدون کتابخانه خارجی،
        // با SVG ساده رندر می‌شود (بخش ۴۵: بدون دلیل فنی Package اضافه نکن)
        $dailySales = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            $total = Order::whereIn('status', ['paid', 'processing', 'preparing', 'shipped', 'delivered'])
                ->whereDate('paid_at', $date->toDateString())
                ->sum('total');

            return ['label' => $date->format('m/d'), 'total' => (int) $total];
        });

        $topCategories = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('categories.name', DB::raw('SUM(order_items.line_total) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $bestSellers = Product::query()
            ->withSum('orderItems as sold_quantity', 'quantity')
            ->orderByDesc('sold_quantity')
            ->take(5)
            ->get();

        return view('admin.reports.index', compact('dailySales', 'topCategories', 'bestSellers'));
    }
}
