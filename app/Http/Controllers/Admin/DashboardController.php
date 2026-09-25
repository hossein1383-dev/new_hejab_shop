<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** داشبورد Admin — بخش ۱۹. */
    public function index(): View
    {
        $todaySales = Order::whereIn('status', ['paid', 'processing', 'preparing', 'shipped', 'delivered'])
            ->whereDate('paid_at', today())
            ->sum('total');

        $monthlySales = Order::whereIn('status', ['paid', 'processing', 'preparing', 'shipped', 'delivered'])
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        $stats = [
            'today_sales' => $todaySales,
            'monthly_sales' => $monthlySales,
            'orders_count' => Order::count(),
            'pending_orders_count' => Order::where('status', 'pending_payment')->count(),
            'customers_count' => User::count(),
            'products_count' => Product::count(),
            // ⚠️ رفع باگ: Inventory::where(...) به‌تنهایی محصولات Soft-Delete
            // شده را هم می‌شمرد چون هیچ رابطه‌ای با Product ندارد و از Scope
            // حذف نرم خبر ندارد. با whereHas این مشکل رفع می‌شود.
            'low_stock_count' => Inventory::where('quantity', '<=', 5)
                ->whereHas('product')
                ->where(function ($query) {
                    $query->whereHas('variant')->orWhere(function ($q) {
                        $q->whereNull('product_variant_id')->whereHas('product', fn($pq) => $pq->doesntHave('variants'));
                    });
                })
                ->count(),
            'pending_reviews_count' => Review::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
