<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;

class WishlistService
{
    public function getOrCreate(User $user): Wishlist
    {
        return Wishlist::firstOrCreate(['user_id' => $user->id]);
    }

    public function add(User $user, Product $product): Wishlist
    {
        $wishlist = $this->getOrCreate($user);
        $wishlist->items()->firstOrCreate(['product_id' => $product->id]);

        return $wishlist->fresh('items.product');
    }

    public function remove(User $user, Product $product): Wishlist
    {
        $wishlist = $this->getOrCreate($user);
        $wishlist->items()->where('product_id', $product->id)->delete();

        return $wishlist->fresh('items.product');
    }

    /**
     * شناسه محصولات علاقه‌مندی کاربر — برای رنگ‌کردن آیکون قلب در کارت
     * محصول (بخش ۲۵). مهمان همیشه آرایه خالی می‌گیرد چون Wishlist نیاز
     * به ورود دارد.
     */
    public function productIdsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $this->getOrCreate($user)->items()->pluck('product_id')->all();
    }
}
