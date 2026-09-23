<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id', 'status', 'abandoned_reminder_sent_at'];

    protected function casts(): array
    {
        return ['abandoned_reminder_sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** جمع کل بر اساس قیمت واحد ثبت‌شده در هر آیتم (بخش ۹: Calculate Total) */
    public function total(): int
    {
        return $this->items->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
