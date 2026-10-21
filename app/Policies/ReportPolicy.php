<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user, Review $review): bool
    {
        return $review->user_id !== $user->id;
    }
}
