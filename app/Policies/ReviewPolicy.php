<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reviews.moderate');
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->hasPermission('reviews.moderate');
    }
}
