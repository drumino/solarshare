<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\ReportTriage;
use App\Services\Ai\ReviewAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolarShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']); // tests déterministes : moteur local
    }

    private function makeEquipment(User $owner): Equipment
    {
        $cat = Category::create(['name' => 'Panneaux', 'slug' => 'panneaux', 'icon' => 'bi-sun']);

        return Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau 100 W', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
    }

    public function test_front_pages_are_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_removed_module_pages_return_404(): void
    {
        $this->get('/reservations')->assertNotFound();
        $this->get('/incidents')->assertNotFound();
    }

    public function test_admin_area_requires_admin_role(): void
    {
        $this->get('/admin')->assertRedirect('/connexion');

        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk();
    }

    public function test_review_moderation_flow(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $equipment = $this->makeEquipment($owner);

        $reservation = Reservation::create([
            'equipment_id' => $equipment->id, 'user_id' => $renter->id,
            'start_date' => now()->subDays(10), 'end_date' => now()->subDays(7),
            'total_price' => 18, 'status' => 'completed',
        ]);

        // Avis propre : publié et visible
        $this->actingAs($renter)->post("/reservations/{$reservation->id}/avis", [
            'rating' => 5, 'comment' => 'Excellent panneau, très efficace, merci !',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $reservation->id, 'sentiment' => 'positive', 'is_visible' => true,
        ]);

        // Deuxième avis interdit sur la même location
        $this->actingAs($renter)->post("/reservations/{$reservation->id}/avis", [
            'rating' => 4, 'comment' => 'Très bien aussi.',
        ])->assertSessionHas('error');

        // Avis toxique : enregistré mais masqué en attente de modération
        $reservation2 = Reservation::create([
            'equipment_id' => $equipment->id, 'user_id' => $renter->id,
            'start_date' => now()->subDays(20), 'end_date' => now()->subDays(17),
            'total_price' => 18, 'status' => 'completed',
        ]);

        $this->actingAs($renter)->post("/reservations/{$reservation2->id}/avis", [
            'rating' => 1, 'comment' => 'Vendeur nul et idiot, ne louez pas chez lui !',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $reservation2->id, 'sentiment' => 'negative', 'is_visible' => false,
        ]);
    }

    public function test_review_analyzer_flags_toxic_comment(): void
    {
        $result = app(ReviewAnalyzer::class)->analyze('Vendeur nul et idiot', 1);

        $this->assertTrue($result['toxic']);
        $this->assertSame('negative', $result['sentiment']);
    }

    public function test_report_triage_local_engine(): void
    {
        config(['solarshare.ai.driver' => 'none']);
        $triage = app(\App\Services\Ai\ReportTriage::class);
        $insult = $triage->classify('insulte', 'tu es un idiot');
        $this->assertSame('high', $insult['severity']);
        $spam = $triage->classify('spam', 'visitez http://x.com');
        $this->assertSame('low', $spam['severity']);
    }

    public function test_duplicate_report_blocked(): void
    {
        $owner = \App\Models\User::factory()->create();
        $renter = \App\Models\User::factory()->create();
        $equipment = $this->makeEquipment($owner);
        $reservation = \App\Models\Reservation::create([
            'equipment_id' => $equipment->id, 'user_id' => $renter->id,
            'start_date' => now()->subDays(10), 'end_date' => now()->subDays(7),
            'total_price' => 18, 'status' => 'completed',
        ]);
        $review = \App\Models\Review::create([
            'reservation_id' => $reservation->id, 'equipment_id' => $equipment->id,
            'user_id' => $owner->id, 'rating' => 5, 'comment' => 'ok',
            'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        \App\Models\ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $renter->id, 'reason' => 'spam', 'status' => 'pending',
        ]);
        $this->actingAs($renter)->post(route('front.reviews.report', $review), [
            'reason' => 'spam',
        ])->assertSessionHas('error');
    }

    public function test_self_report_blocked(): void
    {
        $owner = \App\Models\User::factory()->create();
        $equipment = $this->makeEquipment($owner);
        $review = \App\Models\Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $owner->id, 'rating' => 5, 'comment' => 'ok',
            'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        $this->actingAs($owner)->post(route('front.reviews.report', $review), [
            'reason' => 'spam',
        ])->assertSessionHas('error');
    }

    public function test_auto_hide_at_three_pending_reports(): void
    {
        $owner = \App\Models\User::factory()->create();
        $equipment = $this->makeEquipment($owner);
        $review = \App\Models\Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $owner->id, 'rating' => 5, 'comment' => 'ok',
            'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        foreach (range(1,3) as $i) {
            $u = \App\Models\User::factory()->create();
            $this->actingAs($u)->post(route('front.reviews.report', $review), ['reason' => 'spam']);
        }
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'is_visible' => 0]);
    }

    public function test_admin_report_routes_require_admin(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/admin/review-reports')->assertForbidden();
    }

    public function test_traiter_stores_reviewed_by_and_at(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $owner = \App\Models\User::factory()->create();
        $equipment = $this->makeEquipment($owner);
        $review = \App\Models\Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $owner->id, 'rating' => 5,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        $report = \App\Models\ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $owner->id, 'reason' => 'spam', 'status' => 'pending',
        ]);
        $this->actingAs($admin)->post(route('admin.review-reports.traiter', $report), ['hide_review' => 1])
            ->assertRedirect();
        $report->refresh();
        $this->assertSame('reviewed', $report->status);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);
    }

    public function test_rejeter_sets_dismissed(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $owner = \App\Models\User::factory()->create();
        $equipment = $this->makeEquipment($owner);
        $review = \App\Models\Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $owner->id, 'rating' => 5,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        $report = \App\Models\ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $owner->id, 'reason' => 'spam', 'status' => 'pending',
        ]);
        $this->actingAs($admin)->post(route('admin.review-reports.rejeter', $report))->assertRedirect();
        $report->refresh();
        $this->assertSame('dismissed', $report->status);
    }
}
