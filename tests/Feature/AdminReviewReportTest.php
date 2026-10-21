<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeReview(User $author): Review
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);

        return Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 4,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 0.5,
            'is_visible' => true, 'aspects' => [],
        ]);
    }

    private function makeReport(): ReviewReport
    {
        $author = User::factory()->create(['name' => 'Auteur Avis Alpha']);
        $reporter = User::factory()->create(['name' => 'Signalant Anonyme']);
        $review = $this->makeReview($author);

        return ReviewReport::create([
            'review_id' => $review->id,
            'user_id' => $reporter->id,
            'reason' => 'spam',
            'details' => 'Publicité déguisée.',
            'status' => 'pending',
            'severity' => 'high',
            'category' => 'spam',
            'suggested_action' => 'masquer',
        ]);
    }

    public function test_admin_can_view_report_show_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->makeReport();

        $response = $this->actingAs($admin)->get(route('admin.review-reports.show', $report));

        $response->assertOk();
        $response->assertSee('Signalement #'.$report->id);
        $response->assertSee('Spam / publicité');
        $response->assertSee('Élevé');
        $response->assertSee('Signalant Anonyme');
        $response->assertSee('Auteur Avis Alpha');
    }

    public function test_non_admin_cannot_view_report_show_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $report = $this->makeReport();

        $this->actingAs($user)->get(route('admin.review-reports.show', $report))->assertForbidden();
    }

    public function test_admin_other_review_report_views_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->makeReport();

        $this->actingAs($admin)->get(route('admin.review-reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.review-reports.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.review-reports.edit', $report))->assertOk();
    }
}
