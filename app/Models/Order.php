<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'guest_name', 'guest_email', 'guest_phone', 'address_id',
        'shipping_name', 'shipping_phone', 'shipping_province', 'shipping_city', 'shipping_heropost_city_id', 'shipping_service_type_id',
        'shipping_address_line', 'shipping_postal_code', 'shipping_method',
        'heropost_parcel_id', 'heropost_tracking_code',
        'subtotal', 'discount_total', 'tax_total', 'shipping_cost', 'total',
        'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /**
     * بخش ۵۱: به‌جای شماره سفارش، نام محصولات را نشان می‌دهیم — خواناتر
     * برای مشتری. product_name روی هر OrderItem خودش Snapshot ذخیره‌شده
     * (نیازی به Eager Load رابطه product نیست).
     */
    public function productNamesSummary(int $maxWords = 4): string
    {
        $fullText = $this->items->pluck('product_name')->filter()->implode('، ');
        $words = preg_split('/\s+/u', trim($fullText));

        if (count($words) > $maxWords) {
            return implode(' ', array_slice($words, 0, $maxWords)) . '...';
        }

        return $fullText;
    }

    public const STATUS_LABELS = [
        'pending_payment' => 'در انتظار پرداخت',
        'paid' => 'پرداخت موفق',
        'processing' => 'در حال پردازش',
        'preparing' => 'در حال آماده‌سازی',
        'shipped' => 'ارسال شده',
        'delivered' => 'تحویل‌شده',
        'cancelled' => 'لغوشده',
        'refunded' => 'بازگشت وجه',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
