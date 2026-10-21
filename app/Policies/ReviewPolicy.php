<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function vote(User $user, Review $review): bool
    {
        if (! $review->is_visible || $review->trashed()) {
            return false;
        }
        if ($review->user_id === $user->id) {
            return false;
        }

        return true;
    }

    public function update(User $user, Review $review): bool
    {
        if ($review->trashed() || ! $review->is_visible) {
            return false;
        }
        if ($review->user_id !== $user->id) {
            return false;
        }

        return $review->created_at?->diffInHours(now()) <= 24;
    }

    public function reply(User $user, Review $review): bool
    {
        if (! $review->is_visible || $review->trashed()) {
            return false;
        }
        if ($review->equipment?->owner_id !== $user->id) {
            return false;
        }
        if ($review->user_id === $user->id) {
            return false;
        }
        if ($review->owner_reply === null) {
            return true;
        }
        // editing allowed within 48h of owner_reply_at
        if ($review->owner_reply_at && $review->owner_reply_at->diffInHours(now()) <= 48) {
            return true;
        }

        return false;
    }
}
