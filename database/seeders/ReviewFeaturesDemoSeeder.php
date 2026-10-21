<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\ReviewReport;
use App\Models\ReviewRevision;
use App\Models\ReviewVote;
use App\Models\User;
use App\Notifications\HighSeverityReportNotification;
use App\Services\Ai\LlmClient;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ReviewFeaturesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::where('comment', 'like', '[DEMO] %')->exists()) {
            echo "Demo data already present, nothing added\n";
            return;
        }

        DB::transaction(function () {
            $admin = User::firstOrCreate(['email' => 'demo.admin@solarshare.test'], [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'city' => 'Tunis',
            ]);
            $owner = User::firstOrCreate(['email' => 'demo.owner@solarshare.test'], [
                'name' => 'Demo Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'city' => 'Tunis',
            ]);
            $users = [];
            for ($i = 1; $i <= 4; $i++) {
                $users[$i] = User::firstOrCreate(['email' => "demo.user{$i}@solarshare.test"], [
                    'name' => "Demo User {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'city' => 'Tunis',
                ]);
            }

            $cat = \App\Models\Category::first();
            $equipment = [];
            for ($e = 1; $e <= 3; $e++) {
                $equipment[$e] = Equipment::create([
                    'category_id' => $cat->id,
                    'owner_id' => $owner->id,
                    'title' => "Demo Equipment {$e}",
                    'description' => 'Demo',
                    'type' => 'solar_panel',
                    'power_watts' => 100,
                    'price_per_day' => 10,
                    'deposit' => 50,
                    'condition' => 'bon',
                    'city' => 'Tunis',
                    'status' => 'available',
                ]);
            }

            // completed reservation per reviewing user
            $reservations = [];
            foreach ($equipment as $ei => $eq) {
                foreach ($users as $ui => $u) {
                    $reservations[] = Reservation::create([
                        'equipment_id' => $eq->id,
                        'user_id' => $u->id,
                        'start_date' => now()->subDays(30)->toDateString(),
                        'end_date' => now()->subDays(28)->toDateString(),
                        'total_price' => 20,
                        'status' => 'completed',
                    ]);
                }
            }

            $analyzer = new ReviewAnalyzer(new LlmClient());
            config(['solarshare.ai.driver' => 'none']);

            $reviews = [];
            $comments = [
                'Excellent produit, je recommande vivement !',
                'Correct mais peut mieux faire.',
                'Très décevant, ne fonctionne pas bien.',
            ];

            $idx = 0;
            foreach ($reservations as $r) {
                if (count($reviews) >= 45) break;
                $rating = [5,4,3,2,1][array_rand([5,4,3,2,1])];
                $comment = '[DEMO] ' . $comments[array_rand($comments)];
                $a = $analyzer->analyze($comment, $rating);
                $rev = Review::create([
                    'reservation_id' => $r->id,
                    'equipment_id' => $r->equipment_id,
                    'user_id' => $r->user_id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'sentiment' => $a['sentiment'],
                    'sentiment_score' => $a['score'],
                    'is_visible' => true,
                    'aspects' => $a['aspects'],
                    'criteria_ratings' => $rating >= 4 ? ['qualite' => 5, 'prix' => 4] : null,
                    'flags' => $a['flags'],
                    'created_at' => now()->subMonths(rand(0,11))->subDays(rand(0,27)),
                ]);
                $reviews[] = $rev;
            }

            // suspicious
            $r = $reservations[0];
            $rev = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[1]->id,
                'rating' => 5,
                'comment' => '[DEMO] This is terrible awful garbage',
                'sentiment' => 'negative',
                'sentiment_score' => -0.8,
                'is_visible' => true,
                'created_at' => now(),
            ]);
            $reviews[] = $rev;

            // toxic hidden
            $rev = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[2]->id,
                'rating' => 1,
                'comment' => '[DEMO] connard idiot merde',
                'sentiment' => 'negative',
                'sentiment_score' => -0.9,
                'is_visible' => true,
                'created_at' => now(),
            ]);
            $rev->hide('toxic');
            $reviews[] = $rev;

            // spam link hidden
            $rev = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[3]->id,
                'rating' => 5,
                'comment' => '[DEMO] buy now http://spam.test',
                'sentiment' => 'positive',
                'sentiment_score' => 0.5,
                'is_visible' => true,
                'created_at' => now(),
            ]);
            $rev->hide('spam');
            $reviews[] = $rev;

            // owner replies
            $reviews[0]->update(['owner_reply' => 'Merci', 'owner_reply_at' => now()->subHours(10), 'owner_reply_by' => $owner->id]);
            if (isset($reviews[1])) $reviews[1]->update(['owner_reply' => 'Merci', 'owner_reply_at' => now()->subDays(5), 'owner_reply_by' => $owner->id]);

            // helpful votes
            foreach ([9,5,2] as $k => $count) {
                $rev = $reviews[$k];
                $rev->update(['helpful_count' => $count]);
                for ($v = 0; $v < $count; $v++) {
                    ReviewVote::firstOrCreate(['user_id' => $users[($v % 4) + 1]->id, 'review_id' => $rev->id]);
                }
            }

            // photos
            if (extension_loaded('gd')) {
                $img = imagecreatetruecolor(10,10);
                ob_start();
                imagepng($img);
                $bin = ob_get_clean();
                foreach (array_slice($reviews,0,3) as $rev) {
                    $path = "review-photos/demo-{$rev->id}.png";
                    Storage::disk('public')->put($path, $bin);
                    ReviewPhoto::create(['review_id' => $rev->id, 'path' => $path]);
                }
            } else {
                echo "GD missing, skipping photos\n";
            }

            // recent by user1
            $recent1 = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[1]->id,
                'rating' => 5,
                'comment' => '[DEMO] recent 2h',
                'sentiment' => 'positive',
                'sentiment_score' => 0.9,
                'is_visible' => true,
                'created_at' => now()->subHours(2),
            ]);
            $recent2 = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[1]->id,
                'rating' => 4,
                'comment' => '[DEMO] recent 30h',
                'sentiment' => 'positive',
                'sentiment_score' => 0.7,
                'is_visible' => true,
                'created_at' => now()->subHours(30),
            ]);
            $reviews[] = $recent1; $reviews[] = $recent2;

            // revision rows
            $rev = $reviews[0];
            ReviewRevision::create(['review_id' => $rev->id, 'old_rating' => 3, 'old_comment' => 'old', 'old_sentiment' => 'neutral', 'old_criteria_ratings' => null, 'edited_at' => now()]);
            ReviewRevision::create(['review_id' => $rev->id, 'old_rating' => 4, 'old_comment' => 'old2', 'old_sentiment' => 'positive', 'old_criteria_ratings' => null, 'edited_at' => now()]);

            // soft-deleted
            $sd = Review::create([
                'reservation_id' => null,
                'equipment_id' => $equipment[1]->id,
                'user_id' => $users[4]->id,
                'rating' => 3,
                'comment' => '[DEMO] soft deleted',
                'sentiment' => 'neutral',
                'sentiment_score' => 0,
                'is_visible' => true,
                'created_at' => now(),
            ]);
            $sd->delete();

            // reports: 2 pending stays visible, 3 pending auto-hidden
            $target = $reviews[2];
            foreach (range(1,2) as $i) {
                ReviewReport::create([
                    'review_id' => $target->id,
                    'user_id' => $users[$i]->id,
                    'reason' => 'spam',
                    'status' => 'pending',
                    'severity' => 'low',
                ]);
            }
            $target2 = $reviews[3];
            foreach (range(1,3) as $i) {
                ReviewReport::create([
                    'review_id' => $target2->id,
                    'user_id' => $users[$i]->id,
                    'reason' => 'offensive',
                    'status' => 'pending',
                    'severity' => 'high',
                ]);
            }
            $target2->hide('auto');

            // 8 reports mixed
            $severities = ['low','medium','high','low','medium','high','low','medium'];
            $statuses = ['pending','pending','reviewed','dismissed','pending','reviewed','dismissed','pending'];
            for ($i = 0; $i < 8; $i++) {
                $rep = ReviewReport::create([
                    'review_id' => $reviews[$i % count($reviews)]->id,
                    'user_id' => $users[($i % 4)+1]->id,
                    'reason' => 'spam',
                    'status' => $statuses[$i],
                    'severity' => $severities[$i],
                ]);
                if ($severities[$i] === 'high') {
                    $admin->notify(new HighSeverityReportNotification($rep));
                }
            }

            // top rated equipment: 12 reviews avg ~4.6 (use equipment[1])
            // single 5-star equipment already eq3 has one review? ensure one 5-star only
            // nothing more

            echo "Done\n";
        });
    }
}
