<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Review;
use Illuminate\Contracts\Cache\Repository;

class EquipmentRatingService
{
    public function recompute(Equipment $equipment): void
    {
        $row = $equipment->reviews()
            ->where('is_visible', true)
            ->whereNull('deleted_at')
            ->selectRaw('count(*) as count, avg(rating) as avg')
            ->first();

        $v = (int) ($row->count ?? 0);
        $R = $v > 0 ? (float) ($row->avg ?? 0.0) : 0.0;

        $C = (float) (Review::where('is_visible', true)
            ->whereNull('deleted_at')
            ->avg('rating') ?? 0.0);

        $m = (int) config('solarshare.reviews.bayesian_m', 5);

        $score = self::bayesian($R, $v, $C, $m);

        $equipment->update([
            'reviews_count' => $v,
            'reviews_avg'   => round($R, 2),
            'bayesian_score'=> round($score, 3),
        ]);

        $cache = app(Repository::class);
        $cache->forget('equipment.'.$equipment->id.'_average_rating');
        $cache->forget('equipment.'.$equipment->id.'_criteria_averages');
    }

    public static function bayesian(float $R, int $v, float $C, int $m): float
    {
        return ($v / ($v + $m)) * $R + ($m / ($v + $m)) * $C;
    }
}
