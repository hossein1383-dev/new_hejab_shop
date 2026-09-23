<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    private const CACHE_KEY_TREE = 'categories.tree.active';

    public function create(array $data): Category
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image'] = $data['image']->store('categories', 'public');
        }

        $category = Category::create($data);
        $this->forgetTreeCache();

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('categories', 'public');
        }

        $category->update($data);
        $this->forgetTreeCache();

        return $category->fresh();
    }

    /**
     * حذف Category. اگر زیرمجموعه یا محصول داشته باشد، حذف رد می‌شود
     * تا Data Integrity حفظ شود (بخش ۳ / بخش ۴۴: Data Integrity > Convenience).
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw new \DomainException('این دسته‌بندی دارای زیرمجموعه است و قابل حذف نیست.');
        }

        if ($category->products()->exists()) {
            throw new \DomainException('این دسته‌بندی دارای محصول است و قابل حذف نیست.');
        }

        // آزادسازی Slug تا Unique Index دیتابیس بعد از Soft Delete مانع
        // استفاده دوباره از همان Slug نشود (همان مشکل ProductService::delete)
        $category->update(['slug' => $category->slug . '-deleted-' . $category->id]);
        $category->delete();
        $this->forgetTreeCache();
    }

    /** درخت کامل دسته‌بندی‌های فعال، برای استفاده در Header/Mega Menu، با Cache (بخش ۳۳). */
    public function activeTree()
    {
        return Cache::remember(self::CACHE_KEY_TREE, now()->addHours(6), function () {
            return Category::active()
                ->roots()
                ->with(['children' => fn ($q) => $q->active()])
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function forgetTreeCache(): void
    {
        Cache::forget(self::CACHE_KEY_TREE);
    }
}