<?php

namespace App\Observers;

use App\Models\Equipment;
use App\Models\Review;
use App\Services\EquipmentRatingService;

class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recompute($review);
    }

    public function updated(Review $review): void
    {
        if ($review->isDirty('is_visible')) {
            $this->recompute($review);
        }
    }

    public function deleted(Review $review): void
    {
        $this->recompute($review);
    }

    public function restored(Review $review): void
    {
        $this->recompute($review);
    }

    public function forceDeleted(Review $review): void
    {
        $this->recompute($review);
    }

    private function recompute(Review $review): void
    {
        $equipment = $review->equipment;
        if (! $equipment instanceof Equipment) {
            return;
        }
        app(EquipmentRatingService::class)->recompute($equipment);
    }
}
