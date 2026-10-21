<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Console\Command;

class BackfillAspects extends Command
{
    protected $signature = 'reviews:backfill-aspects';
    protected $description = 'Backfill aspects on existing reviews';

    public function handle(ReviewAnalyzer $analyzer): int
    {
        Review::whereNull('aspects')->chunk(100, function ($reviews) use ($analyzer) {
            foreach ($reviews as $review) {
                $result = $analyzer->analyze($review->comment, (int) $review->rating);
                $review->update(['aspects' => $result['aspects']]);
            }
        });
        $this->info('Done.');
        return self::SUCCESS;
    }
}
