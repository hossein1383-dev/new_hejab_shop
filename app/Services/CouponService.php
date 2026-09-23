<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponService
{
    /**
     * پیدا کردن یک کوپن معتبر بر اساس کد. اعتبارسنجی‌های عمومی (بدون در نظر
     * گرفتن محتوای سبد) اینجا انجام می‌شود؛ اعتبارسنجی مرتبط با سبد در
     * calculateDiscount است. همه Validation ها همیشه در Backend انجام
     * می‌شوند (بخش ۱۶).
     */
    public function findValidCoupon(string $code, ?User $user, int $subtotal): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isCurrentlyActive()) {
            throw new \DomainException('کد تخفیف نامعتبر یا منقضی شده است.');
        }

        if ($subtotal < $coupon->min_order_amount) {
            throw new \DomainException('حداقل مبلغ سفارش برای این کد تخفیف ' . number_format($coupon->min_order_amount) . ' تومان است.');
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            throw new \DomainException('ظرفیت استفاده از این کد تخفیف تمام شده است.');
        }

        if ($user && $coupon->per_user_limit !== null) {
            $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $coupon->per_user_limit) {
                throw new \DomainException('شما قبلاً از این کد تخفیف استفاده کرده‌اید.');
            }
        }

        if ($coupon->restrictedUsers()->exists() && (! $user || ! $coupon->restrictedUsers()->where('users.id', $user->id)->exists())) {
            throw new \DomainException('این کد تخفیف برای شما قابل استفاده نیست.');
        }

        return $coupon;
    }

    /**
     * محاسبه مبلغ تخفیف روی خطوط سبد. اگر کوپن به محصولات/دسته‌بندی خاصی
     * محدود شده باشد، فقط روی همان زیرمجموعه اعمال می‌شود، نه کل سبد.
     *
     * @param  Collection<int, array{product: \App\Models\Product, line_total: int}>  $lines
     */
    public function calculateDiscount(Coupon $coupon, Collection $lines): int
    {
        $hasProductRestriction = $coupon->products()->exists();
        $hasCategoryRestriction = $coupon->categories()->exists();

        if ($hasProductRestriction || $hasCategoryRestriction) {
            $restrictedProductIds = $coupon->products()->pluck('products.id')->all();
            $restrictedCategoryIds = $coupon->categories()->pluck('categories.id')->all();

            $eligibleLines = $lines->filter(function ($line) use ($restrictedProductIds, $restrictedCategoryIds) {
                return in_array($line['product']->id, $restrictedProductIds, true)
                    || in_array($line['product']->category_id, $restrictedCategoryIds, true);
            });
        } else {
            $eligibleLines = $lines;
        }

        $eligibleSubtotal = $eligibleLines->sum('line_total');

        if ($eligibleSubtotal <= 0) {
            throw new \DomainException('این کد تخفیف روی محصولات سبد شما قابل اعمال نیست.');
        }

        $discount = $coupon->type === 'percentage'
            ? (int) round($eligibleSubtotal * $coupon->value / 100)
            : min($coupon->value, $eligibleSubtotal);

        if ($coupon->type === 'percentage' && $coupon->max_discount_amount !== null) {
            $discount = min($discount, $coupon->max_discount_amount);
        }

        return $discount;
    }

    public function recordUsage(Coupon $coupon, ?User $user, Order $order, int $discountAmount): void
    {
        $coupon->usages()->create([
            'user_id' => $user?->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
        ]);
    }
}
