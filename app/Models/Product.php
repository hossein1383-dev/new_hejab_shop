<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'sku', 'barcode', 'brand_id', 'category_id',
        'description', 'short_description',
        'price', 'compare_price', 'cost_price', 'tax_percent',
        'weight', 'dimensions',
        'status', 'is_featured', 'is_new', 'is_bestseller',
        'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_price' => 'integer',
            'cost_price' => 'integer',
            'tax_percent' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_bestseller' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * بخش ۵۸: اگر واریانت‌های محصول (مثلاً جنس ندا/حریر) قیمت‌های متفاوت
     * داشته باشند، به‌جای یک قیمت ثابت نادرست، «از X تومان» نمایش داده
     * می‌شود. رابطه variants باید از قبل Eager Load شده باشد.
     */
    public function priceRange(): array
    {
        $prices = $this->variants
            ->filter(fn ($variant) => $variant->status === 'active')
            ->map(fn ($variant) => $variant->price ?? $this->price);

        if ($prices->isEmpty()) {
            return ['min' => $this->price, 'max' => $this->price, 'has_range' => false];
        }

        $min = $prices->min();
        $max = $prices->max();

        return ['min' => $min, 'max' => $max, 'has_range' => $min !== $max];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->approved();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function averageRating(): ?float
    {
        return $this->approvedReviews->isEmpty() ? null : round($this->approvedReviews->avg('rating'), 1);
    }

    public function reviewsCount(): int
    {
        return $this->approvedReviews->count();
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class)->whereNull('product_variant_id');
    }

    /** آیا این محصول دارای Variant است؟ (بخش ۷) */
    public function hasVariants(): bool
    {
        return $this->variants->isNotEmpty();
    }

    public function thumbnail(): ?ProductImage
    {
        return $this->images->firstWhere('is_thumbnail', true) ?? $this->images->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /** آیا این محصول در حال حاضر تخفیف دارد؟ */
    public function hasDiscount(): bool
    {
        return $this->compare_price !== null && $this->compare_price > $this->price;
    }
}
