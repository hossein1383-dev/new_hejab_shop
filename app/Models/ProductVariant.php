<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price', 'compare_price', 'weight', 'image', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_price' => 'integer',
            'weight' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /** قیمت مؤثر: اگر Variant قیمت مخصوص نداشته باشد، از قیمت Product اصلی استفاده می‌شود. */
    public function effectivePrice(): int
    {
        return $this->price ?? $this->product->price;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
