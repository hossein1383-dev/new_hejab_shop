<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * ثبت یک حرکت موجودی و به‌روزرسانی مقدار فعلی، به‌صورت Atomic.
     * از lockForUpdate استفاده می‌شود تا در برابر Race Condition (دو درخواست هم‌زمان)
     * محافظت شود (بخش ۸ و ۳۵).
     */
    public function recordMovement(Product $product, ?ProductVariant $variant, string $type, int $quantity, ?string $reference = null, ?int $userId = null, ?string $note = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $variant, $type, $quantity, $reference, $userId, $note) {
            $inventory = Inventory::query()->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->lockForUpdate()->first();

            if (!$inventory) {
                $inventory = Inventory::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $newQuantity = $inventory->quantity + $quantity;

            if ($newQuantity < 0) {
                throw new \DomainException('موجودی کافی نیست.');
            }

            $inventory->update(['quantity' => $newQuantity]);

            // بخش ۶۰: اگر این تغییر مربوط به یک واریانت بود، موجودی «پایه»
            // (product_variant_id=null) هم با مجموع کل هم‌مقدار می‌شود — چون
            // صفحه محصول فقط موجودی پایه را برای «موجود/ناموجود» چک می‌کند.
            if ($variant) {
                Inventory::updateOrCreate(['product_id' => $product->id, 'product_variant_id' => null], ['quantity' => $this->totalAvailableQuantity($product)]);
            }

            return InventoryMovement::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference' => $reference,
                'user_id' => $userId,
                'note' => $note,
            ]);
        });
    }

    /** موجودی قابل فروش فعلی (فیزیکی − رزروشده). */
    public function availableQuantity(Product $product, ?ProductVariant $variant): int
    {
        $inventory = Inventory::query()->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->first();

        return $inventory?->availableQuantity() ?? 0;
    }

    /**
     * بخش ۶۰: مجموع کل موجودی محصول — جمع تمام واریانت‌ها با هم (اگر
     * واریانت نداشته باشد، همان یک ردیف پایه). همیشه Live محاسبه می‌شود،
     * هیچ عدد جداگانه‌ای ذخیره نمی‌شود — پس هروقت واریانتی اضافه/کم یا
     * موجودی‌اش تغییر کند، این عدد خودکار همراهش عوض می‌شود.
     */
    public function totalAvailableQuantity(Product $product): int
    {
        $hasVariants = ProductVariant::where('product_id', $product->id)->exists();

        return Inventory::query()->where('product_id', $product->id)->when($hasVariants, fn($q) => $q->whereNotNull('product_variant_id'))->get()->sum(fn(Inventory $inventory) => $inventory->availableQuantity());
    }

    /**
     * رزرو مقدار مشخصی از موجودی (مثلاً هنگام شروع Checkout — بخش ۸ و ۱۴).
     * در برابر Overselling با lockForUpdate محافظت می‌شود.
     */
    public function reserve(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        DB::transaction(function () use ($product, $variant, $quantity) {
            $inventory = Inventory::query()->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->lockForUpdate()->first();

            if (!$inventory || $inventory->availableQuantity() < $quantity) {
                throw new \DomainException('موجودی کافی برای رزرو وجود ندارد.');
            }

            $inventory->increment('reserved_quantity', $quantity);
        });
    }

    /** آزاد کردن رزرو (مثلاً Checkout لغو شد یا Timeout شد — بخش ۱۴). */
    public function release(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        DB::transaction(function () use ($product, $variant, $quantity) {
            $inventory = Inventory::query()->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->lockForUpdate()->first();

            if ($inventory) {
                $inventory->update([
                    'reserved_quantity' => max(0, $inventory->reserved_quantity - $quantity),
                ]);
            }
        });
    }

    /**
     * تبدیل رزرو به فروش قطعی: وقتی پرداخت موفق تایید می‌شود، مقدار رزروشده
     * هم از reserved_quantity کم می‌شود و هم به‌صورت واقعی از quantity کسر
     * می‌شود (ثبت Movement نوع sale) — بخش ۸ و ۱۴.
     */
    public function confirmReservedSale(Product $product, ?ProductVariant $variant, int $quantity, ?string $reference = null): void
    {
        DB::transaction(function () use ($product, $variant, $quantity, $reference) {
            $inventory = Inventory::query()->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->lockForUpdate()->first();

            if ($inventory) {
                $inventory->update([
                    'reserved_quantity' => max(0, $inventory->reserved_quantity - $quantity),
                ]);
            }

            $this->recordMovement($product, $variant, 'sale', -$quantity, $reference);
        });
    }
}
