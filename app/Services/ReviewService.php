<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    /**
     * ثبت Review جدید با وضعیت pending (نیازمند تایید Admin — بخش ۱۷).
     * هر کاربر فقط یک Review برای هر محصول می‌تواند ثبت کند (Unique Constraint).
     */
    public function submit(User $user, Product $product, int $rating, ?string $comment, array $images = []): Review
    {
        if (Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            throw new \DomainException('شما قبلاً برای این محصول نظر ثبت کرده‌اید.');
        }

        return DB::transaction(function () use ($user, $product, $rating, $comment, $images) {
            $review = Review::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => $comment,
                'status' => 'pending',
            ]);

            foreach ($images as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $review->images()->create(['path' => $image->store('reviews', 'public')]);
                }
            }

            return $review->fresh('images');
        });
    }

    /**
     * تایید/رد Review توسط Admin، با ثبت Audit Log (بخش ۱۷: «عملیات Moderation
     * باید Authorization و Audit داشته باشد»).
     */
    public function moderate(Review $review, string $status, User $moderator): Review
    {
        $oldStatus = $review->status;

        $review->update([
            'status' => $status,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->auditLogService->log(
            $moderator,
            "review.{$status}",
            $review,
            ['status' => $oldStatus],
            ['status' => $status]
        );

        return $review->fresh();
    }
}
