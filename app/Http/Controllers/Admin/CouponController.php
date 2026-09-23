<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::query()
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%' . strtoupper($request->input('search')) . '%'))
            ->withCount('usages')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.form', ['coupon' => new Coupon()]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('order_success', 'کد تخفیف ایجاد شد.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(StoreCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('order_success', 'کد تخفیف ویرایش شد.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('order_success', 'کد تخفیف حذف شد.');
    }
}
