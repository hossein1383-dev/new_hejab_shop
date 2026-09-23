<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_id', 'user_id', 'rating', 'comment', 'status', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'moderated_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * میانگین امتیاز + تعداد نظر چند محصول را در یک کوئری واحد برمی‌گرداند —
     * بخش ۲۵. به‌جای تکیه به Relation محصول (approvedReviews) که پشت
     * Cache::remember صفحه اصلی، Eager Load آن درست منتقل نمی‌شد و باعث
     * N+1 واقعی می‌شد.
     * @return array<int, array{average: float, count: int}>
     */
    public static function ratingsMapFor(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return self::query()
            ->whereIn('product_id', $productIds)
            ->where('status', 'approved')
            ->selectRaw('product_id, AVG(rating) as average, COUNT(*) as count')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn ($row) => ['average' => round((float) $row->average, 1), 'count' => (int) $row->count])
            ->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
