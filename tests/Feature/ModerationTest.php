<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Services\ModerationStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
        Cache::flush();
    }

    private function makeReview(User $author, array $overrides = []): Review
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        return Review::create(array_merge([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 4,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 0.5,
            'is_visible' => true, 'aspects' => [],
        ], $overrides));
    }

    public function test_non_admin_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/admin/moderation')->assertForbidden();
    }

    public function test_guest_redirected(): void
    {
        $this->get('/admin/moderation')->assertRedirect('/connexion');
    }

    public function test_csv_has_headers_and_bom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $res = $this->actingAs($admin)->get('/admin/moderation/export');
        $res->assertOk();
        $content = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('id,date,auteur', $content);
    }

    public function test_kpis_are_correct(): void
    {
        $author = User::factory()->create();
        $this->makeReview($author, ['is_visible' => true]);
        $this->makeReview($author, ['is_visible' => false]);
        $deleted = $this->makeReview($author);
        $deleted->delete();
        $this->makeReview($author);
        $this->makeReview($author);
        $admin = User::factory()->create();
        ReviewReport::create(['review_id' => Review::first()->id, 'user_id' => $admin->id, 'reason' => 'spam', 'status' => 'pending']);
        ReviewReport::create(['review_id' => Review::first()->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'pending']);
        $svc = app(ModerationStatsService::class);
        $kpis = $svc->kpis([]);
        $this->assertSame(4, $kpis['total']); // 5 - 1 deleted
        $this->assertSame(1, $kpis['hidden']);
        $this->assertSame(2, $kpis['pending_reports']);
    }

    public function test_sentiment_distribution_is_correct(): void
    {
        $author = User::factory()->create();
        for ($i = 0; $i < 2; $i++) $this->makeReview($author, ['sentiment' => 'positive']);
        $this->makeReview($author, ['sentiment' => 'neutral']);
        for ($i = 0; $i < 3; $i++) $this->makeReview($author, ['sentiment' => 'negative']);
        $svc = app(ModerationStatsService::class);
        $dist = $svc->sentimentDistribution([]);
        $this->assertSame(2, $dist['positive']);
        $this->assertSame(1, $dist['neutral']);
        $this->assertSame(3, $dist['negative']);
    }

    public function test_monthly_average_returns_12_entries(): void
    {
        $author = User::factory()->create();
        $r1 = $this->makeReview($author, ['rating' => 4]);
        $r1->forceFill(['created_at' => now()->subMonth()])->saveQuietly();
        $r2 = $this->makeReview($author, ['rating' => 2]);
        $r2->forceFill(['created_at' => now()])->saveQuietly();
        $svc = app(ModerationStatsService::class);
        $monthly = $svc->monthlyAverageRating([]);
        $this->assertCount(12, $monthly);
        $nonNull = array_filter($monthly, fn ($v) => $v !== null);
        $this->assertCount(2, $nonNull);
    }

    public function test_top_aspects_counts_are_correct(): void
    {
        $author = User::factory()->create();
        $this->makeReview($author, ['aspects' => ['prix', 'qualité']]);
        $this->makeReview($author, ['aspects' => ['prix']]);
        $this->makeReview($author, ['aspects' => ['ponctualité']]);
        $svc = app(ModerationStatsService::class);
        $aspects = $svc->topAspects([]);
        $this->assertSame(2, $aspects['prix']);
        $this->assertSame(1, $aspects['qualité']);
    }

    public function test_report_queue_order_high_medium_low(): void
    {
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $admin = User::factory()->create();
        $u2 = User::factory()->create();
        ReviewReport::create(['review_id' => $review->id, 'user_id' => $admin->id, 'reason' => 'spam', 'status' => 'pending', 'severity' => 'low']);
        ReviewReport::create(['review_id' => $review->id, 'user_id' => $u2->id, 'reason' => 'spam', 'status' => 'pending', 'severity' => 'high']);
        $svc = app(ModerationStatsService::class);
        $queue = $svc->reportQueue();
        $this->assertSame('high', $queue->first()->severity);
    }

    public function test_stats_are_cached(): void
    {
        $author = User::factory()->create();
        $this->makeReview($author);
        $svc = app(ModerationStatsService::class);
        $svc->kpis([]);
        DB::enableQueryLog();
        $svc->kpis([]);
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_csv_escapes_formula_injection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $this->makeReview($author, ['comment' => '=SUM(A1)']);
        $res = $this->actingAs($admin)->get('/admin/moderation/export?sentiment=positive');
        $content = $res->streamedContent();
        $this->assertStringContainsString("'=SUM(A1)", $content);
    }

    public function test_csv_respects_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $this->makeReview($author, ['sentiment' => 'negative', 'comment' => 'negatif']);
        $this->makeReview($author, ['sentiment' => 'positive', 'comment' => 'positif']);
        $res = $this->actingAs($admin)->get('/admin/moderation/export?sentiment=positive');
        $content = $res->streamedContent();
        $this->assertStringContainsString('positif', $content);
        $this->assertStringNotContainsString('negatif', $content);
    }

public function test_analyzer_returns_aspects_with_local_engine(): void
    {
        $analyzer = app(\App\Services\Ai\ReviewAnalyzer::class);
        $result = $analyzer->analyze('Prix trop cher et livraison en retard', 2);
        $this->assertContains('prix', $result['aspects']);
        $this->assertContains('ponctualitÃ©', $result['aspects']);
    }

    public function test_kpis_change_when_equipment_id_set(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $eq1 = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $author->id, 'title' => 'Panneau A', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        $eq2 = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $author->id, 'title' => 'Panneau B', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        $this->makeReview($author, ['equipment_id' => $eq1->id, 'rating' => 5]);
        $this->makeReview($author, ['equipment_id' => $eq2->id, 'rating' => 1]);
        $svc = app(ModerationStatsService::class);
        $all = $svc->kpis([]);
        $this->assertSame(2, $all['total']);
        $filtered = $svc->kpis(['equipment_id' => $eq1->id]);
        $this->assertSame(1, $filtered['total']);
    }

    public function test_invalid_equipment_id_is_ignored(): void
    {
        $author = User::factory()->create();
        $this->makeReview($author);
        $svc = app(ModerationStatsService::class);
        $kpis = $svc->kpis(['equipment_id' => 999999]);
        $this->assertSame(1, $kpis['total']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/moderation?equipment_id=abc')
            ->assertOk();
    }

    public function test_csv_respects_equipment_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $eq1 = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $author->id, 'title' => 'Panneau A', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        $eq2 = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $author->id, 'title' => 'Panneau B', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        $this->makeReview($author, ['equipment_id' => $eq1->id, 'comment' => 'avis eq1']);
        $this->makeReview($author, ['equipment_id' => $eq2->id, 'comment' => 'avis eq2']);
        $res = $this->actingAs($admin)->get('/admin/moderation/export?equipment_id=' . $eq1->id);
        $content = $res->streamedContent();
        $this->assertStringContainsString('avis eq1', $content);
        $this->assertStringNotContainsString('avis eq2', $content);
    }
    public function test_admin_gets_pdf_200(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $res = $this->actingAs($admin)->get('/admin/moderation/pdf');
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
    }

    public function test_non_admin_gets_403_on_pdf(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/admin/moderation/pdf')->assertForbidden();
    }

    public function test_guest_redirected_on_pdf(): void
    {
        $this->get('/admin/moderation/pdf')->assertRedirect('/connexion');
    }
}
