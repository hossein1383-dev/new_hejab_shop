<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

/**
 * دستور یک‌باره (One-Time) — قبل از رفع باگ Soft-Delete + Unique، رکوردهای
 * حذف‌شده SKU/Slug اصلی خود را نگه داشته بودند و مانع استفاده دوباره از آن
 * مقدار می‌شدند. این دستور همه رکوردهای حذف‌شده قدیمی را پیدا کرده و همان
 * پسوند '-deleted-{id}' را که الان برای حذف‌های جدید خودکار است، رویشان
 * اعمال می‌کند. اجرای دوباره این دستور بی‌خطر است (Idempotent) چون رکوردهایی
 * که از قبل پسوند دارند را نادیده می‌گیرد.
 */
class FixLegacySoftDeletedUniqueFieldsCommand extends Command
{
    protected $signature = 'fix:legacy-soft-deleted-unique-fields';
    protected $description = 'SKU/Slug رکوردهای حذف‌شده قدیمی (قبل از رفع باگ Soft-Delete) را آزاد می‌کند';

    public function handle(): int
    {
        $productCount = 0;
        Product::onlyTrashed()->get()->each(function (Product $product) use (&$productCount) {
            if (! str_contains($product->sku, '-deleted-')) {
                $product->update([
                    'sku' => $product->sku . '-deleted-' . $product->id,
                    'slug' => $product->slug . '-deleted-' . $product->id,
                ]);
                $productCount++;
            }
        });

        $categoryCount = 0;
        Category::onlyTrashed()->get()->each(function (Category $category) use (&$categoryCount) {
            if (! str_contains($category->slug, '-deleted-')) {
                $category->update(['slug' => $category->slug . '-deleted-' . $category->id]);
                $categoryCount++;
            }
        });

        $variantCount = 0;
        ProductVariant::onlyTrashed()->get()->each(function (ProductVariant $variant) use (&$variantCount) {
            if (! str_contains($variant->sku, '-deleted-')) {
                $variant->update(['sku' => $variant->sku . '-deleted-' . $variant->id]);
                $variantCount++;
            }
        });

        $this->info("{$productCount} محصول، {$categoryCount} دسته‌بندی، {$variantCount} Variant حذف‌شده قدیمی اصلاح شد.");

        return self::SUCCESS;
    }
}
