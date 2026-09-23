<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'image', 'image_mobile', 'link_url', 'position', 'sort_order', 'status', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** اگر تصویر مخصوص موبایل آپلود نشده باشد، همان تصویر دسکتاپ استفاده می‌شود. */
    public function mobileImage(): ?string
    {
        return $this->image_mobile ?: $this->image;
    }

    /** اگر تصویر دسکتاپ آپلود نشده باشد (چون الان هردو اختیاری‌اند)، تصویر موبایل جایگزین می‌شود. */
    public function desktopImage(): ?string
    {
        return $this->image ?: $this->image_mobile;
    }

    public function scopeActiveNow($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
