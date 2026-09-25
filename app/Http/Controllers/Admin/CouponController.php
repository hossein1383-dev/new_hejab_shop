<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SmsGatewayContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function __construct(private readonly SmsGatewayContract $smsGateway)
    {
    }
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

    /**
     * ارسال پیامک هدیه این کد تخفیف به همه مشتریانی که شماره تلفن دارند —
     * بخش ۵۶. هیچ‌وقت کل عملیات را با یک خطای تکی متوقف نمی‌کند.
     */
    public function sendGiftSms(Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $customers = User::whereNotNull('phone')->whereDoesntHave('roles')->get();
        $sentCount = 0;

        foreach ($customers as $customer) {
            try {
                $this->smsGateway->sendCouponGift($customer->phone, $customer->smsDisplayName(), $coupon->code);
                $sentCount++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ارسال پیامک هدیه تخفیف ناموفق بود', [
                    'coupon_id' => $coupon->id,
                    'user_id' => $customer->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('order_success', "پیامک برای {$sentCount} مشتری از {$customers->count()} نفر ارسال شد.");
    }
}
