<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * ایجاد محصول به همراه تصاویر و تگ‌ها در یک Transaction
     * (چند جدول باید هم‌زمان و Atomic درست شوند — بخش ۳۵).
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $images = $data['images'] ?? [];
            $tags = $data['tags'] ?? [];
            unset($data['images'], $data['tags']);

            $product = Product::create($data);

            $this->syncImages($product, $images);
            $this->syncTags($product, $tags);

            return $product->fresh(['images', 'tags']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $images = $data['images'] ?? null;
            $tags = $data['tags'] ?? null;
            unset($data['images'], $data['tags']);

            $product->update($data);

            if ($images !== null) {
                $this->syncImages($product, $images);
            }
            if ($tags !== null) {
                $this->syncTags($product, $tags);
            }

            return $product->fresh(['images', 'tags']);
        });
    }

    /**
     * حذف نرم (Soft Delete) محصول. SKU/Slug قبل از حذف با یک پسوند یکتا
     * تغییر می‌کند تا از تداخل با Unique Index دیتابیس (که Soft Delete را
     * نمی‌شناسد و مقدار حذف‌شده را هنوز «اشغال‌شده» می‌بیند) جلوگیری شود؛
     * تصویر فیزیکی حذف نمی‌شود تا در صورت Restore قابل بازیابی باشد
     * (Data Integrity، بخش ۳ و ۴۴).
     */
    public function delete(Product $product): void
    {
        $product->update([
            'sku' => $product->sku . '-deleted-' . $product->id,
            'slug' => $product->slug . '-deleted-' . $product->id,
        ]);
        $product->delete();
    }

    private function syncImages(Product $product, array $images): void
    {
        foreach ($images as $index => $file) {
            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $path = $file->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'is_thumbnail' => $index === 0 && ! $product->images()->where('is_thumbnail', true)->exists(),
                'sort_order' => $index,
            ]);
        }
    }

    private function syncTags(Product $product, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(function (string $name) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            });

        $product->tags()->sync($tagIds);
    }

    /**
     * لیست محصولات با فیلتر/سورت انجام‌شده در Database (نه در PHP) طبق بخش ۱۰.
     * نتیجه با TTL کوتاه Cache می‌شود (بخش ۳۲/۳۳). چون ترکیب فیلترها زیاد است،
     * Invalidation دقیق به‌صرفه نیست؛ TTL کوتاه (Eventual Consistency، بخش ۴۶:
     * Assumption مستند) به‌جای پیچیده‌کردن معماری با Cache Tags انتخاب شد.
     */
    public function paginateForStorefront(array $filters): LengthAwarePaginator
    {
        $page = (int) request()->get('page', 1);
        $cacheKey = 'products.list.' . md5(json_encode($filters) . '|page=' . $page);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            $query = Product::query()->active()->with(['images', 'brand', 'category', 'variants']);

            if (! empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (! empty($filters['brand_ids'])) {
                $query->whereIn('brand_id', (array) $filters['brand_ids']);
            }
            if (! empty($filters['min_price'])) {
                $query->where('price', '>=', (int) $filters['min_price']);
            }
            if (! empty($filters['max_price'])) {
                $query->where('price', '<=', (int) $filters['max_price']);
            }
            if (! empty($filters['q'])) {
                $term = '%' . $filters['q'] . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                });
            }

            match ($filters['sort'] ?? 'newest') {
                'cheapest' => $query->orderBy('price'),
                'expensive' => $query->orderByDesc('price'),
                'bestseller' => $query->orderByDesc('is_bestseller'),
                default => $query->orderByDesc('created_at'),
            };

            return $query->paginate($filters['per_page'] ?? 24)->withQueryString();
        });
    }

    /**
     * برندهای موجود و بازه قیمت محصولات فعال — برای پر کردن فیلتر (بخش ۱۰).
     * فقط بر اساس Category فیلتر می‌شود (نه سایر فیلترها) تا لیست گزینه‌ها
     * هنگام انتخاب یک فیلتر دیگر ناگهان خالی نشود.
     */
    public function filterFacets(?int $categoryId): array
    {
        $query = Product::query()->active();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return [
            'brands' => Brand::query()
                ->whereIn('id', (clone $query)->whereNotNull('brand_id')->pluck('brand_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name']),
            'min_price' => (clone $query)->min('price'),
            'max_price' => (clone $query)->max('price'),
        ];
    }
}
