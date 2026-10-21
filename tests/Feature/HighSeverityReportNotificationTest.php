<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Notifications\HighSeverityReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Scénario complet : signalement front-office à sévérité élevée -> notification
 * réelle (base + mail), cloche admin, clic = marqué comme lu.
 * Aucun Notification::fake() : on vérifie la chaîne de production.
 */
class HighSeverityReportNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['solarshare.ai.driver' => 'none']);
        config(['queue.default' => 'sync']);
        config(['mail.default' => 'array']);
    }

    private function makeVisibleReview(User $author): Review
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);

        return Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 1,
            'comment' => 'Panneau ne fonctionne pas', 'sentiment' => 'negative', 'sentiment_score' => -0.5,
            'is_visible' => true,
        ]);
    }

    public function test_front_report_with_real_reason_value_notifies_every_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secondAdmin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $response = $this->actingAs($reporter)->post(route('front.reviews.report', $review), [
            'reason' => 'offensive',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('review_reports', [
            'review_id' => $review->id,
            'user_id' => $reporter->id,
            'reason' => 'offensive',
            'severity' => 'high',
            'status' => 'pending',
        ]);

        $rows = DB::table('notifications')->where('type', HighSeverityReportNotification::class)->get();
        $this->assertCount(2, $rows, 'Une notification par administrateur.');
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $secondAdmin->notifications()->count());
        $this->assertSame(0, $reporter->notifications()->count(), 'Un non-admin ne reçoit rien.');
        $this->assertSame(0, $author->notifications()->count(), 'Un non-admin ne reçoit rien.');

        $notification = $admin->notifications()->first();
        $this->assertSame(HighSeverityReportNotification::class, $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            route('admin.review-reports.show', $notification->data['report_id'], absolute: false),
            $notification->data['link'],
            'La notification pointe vers la fiche du signalement (URL relative).',
        );
    }

    public function test_admin_bell_shows_unread_count_and_report_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $this->actingAs($reporter)->post(route('front.reviews.report', $review), ['reason' => 'offensive']);

        $report = ReviewReport::where('review_id', $review->id)->latest('id')->first();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/badge bg-danger"[^>]*>\s*1\s*</',
            $html,
            'La cloche admin affiche le compteur de notifications non lues.',
        );
        $this->assertStringContainsString(
            route('admin.review-reports.show', $report, absolute: false),
            $html,
            'La cloche admin renvoie vers le signalement (URL relative).',
        );
    }

    public function test_click_through_marks_notification_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $this->actingAs($reporter)->post(route('front.reviews.report', $review), ['reason' => 'offensive']);

        $notification = $admin->notifications()->firstOrFail();
        $report = ReviewReport::findOrFail($notification->data['report_id']);

        $response = $this->actingAs($admin)->get(route('notifications.read', $notification->id));

        $response->assertRedirect(route('admin.review-reports.show', $report, absolute: false));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_notification_survives_report_without_category_and_suggested_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $report = ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $reporter->id, 'reason' => 'other',
            'details' => 'Signalement manuel sans classification.', 'status' => 'pending',
            'severity' => 'high', 'category' => null, 'suggested_action' => null,
        ]);

        $admin->notify(new HighSeverityReportNotification($report));

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame(HighSeverityReportNotification::class, $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame(route('admin.review-reports.show', $report, absolute: false), $notification->data['link']);
    }

    public function test_notification_survives_soft_deleted_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $report = ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $reporter->id, 'reason' => 'offensive',
            'status' => 'pending', 'severity' => 'high', 'category' => 'insulte', 'suggested_action' => 'masquer',
        ]);

        $review->delete();
        $this->assertNull($report->fresh()->review, 'Un avis soft-deleted n\'est plus résolu.');

        $admin->notify(new HighSeverityReportNotification($report));

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame(HighSeverityReportNotification::class, $notification->type);
        $this->assertSame('', $notification->data['excerpt']);
        $this->assertSame(route('admin.review-reports.show', $report, absolute: false), $notification->data['link']);
    }

    public function test_stored_relative_link_starts_with_slash_and_click_returns_200(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create(['role' => 'user']);
        $review = $this->makeVisibleReview($author);

        $this->actingAs($reporter)->post(route('front.reviews.report', $review), ['reason' => 'offensive']);

        $notification = $admin->notifications()->firstOrFail();

        // Stored link must be relative (starts with "/").
        $this->assertStringStartsWith('/', $notification->data['link'], 'Le lien stocké est relatif.');
        $this->assertStringNotContainsString('://', $notification->data['link'], 'Le lien stocké ne contient pas de schéma/hôte.');

        // Clicking the stored link as admin must land on the report page (200).
        $response = $this->actingAs($admin)->get($notification->data['link']);
        $response->assertOk();
        $response->assertSee('Signalement');
    }
}
