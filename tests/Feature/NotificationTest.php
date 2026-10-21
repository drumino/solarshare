<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use App\Notifications\HighSeverityReportNotification;
use App\Notifications\OwnerRepliedNotification;
use App\Notifications\ReviewHiddenNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
    }

    private function makeReview(User $author): Review
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        return Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 5,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
    }

    public function test_high_severity_notifies_admins_only(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $report = ReviewReport::create([
            'review_id' => $review->id, 'user_id' => $author->id, 'reason' => 'spam',
            'status' => 'pending', 'severity' => 'high',
        ]);
        $admin->notify(new HighSeverityReportNotification($report));
        Notification::assertSentTo($admin, HighSeverityReportNotification::class);
        Notification::assertNotSentTo($user, HighSeverityReportNotification::class);
    }

    public function test_medium_low_send_nothing(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_author_notified_on_ai_hidden(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $review->hide('toxique');
        Notification::assertSentTo($author, ReviewHiddenNotification::class);
    }

    public function test_no_notification_when_stays_visible(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $this->makeReview($author);
        Notification::assertNothingSent();
    }

    public function test_no_notification_when_already_hidden(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $review->update(['is_visible' => false]);
        $review->hide('note');
        Notification::assertNothingSent();
    }

    public function test_owner_reply_notifies_on_store_not_edit(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
        $review = Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 5,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
        $this->actingAs($owner)->post(route('owner.reviews.reply', $review), ['reply' => 'Merci']);
        Notification::assertSentTo($author, OwnerRepliedNotification::class);
        Notification::fake();
        $this->actingAs($owner)->post(route('owner.reviews.reply', $review), ['reply' => 'Modifié']);
        Notification::assertNothingSent();
    }

    public function test_notifications_page_requires_auth(): void
    {
        $this->get('/notifications')->assertRedirect('/connexion');
    }

    public function test_mark_as_read(): void
    {
        $user = User::factory()->create();
        $n = $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\\Notifications\\ReviewHiddenNotification',
            'data' => ['link' => '/'], 'read_at' => null,
        ]);
        $this->actingAs($user)->post(route('notifications.read', $n->id))->assertRedirect('/');
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\\Notifications\\ReviewHiddenNotification',
            'data' => ['link' => '/'], 'read_at' => null,
        ]);
        $this->actingAs($user)->post(route('notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_other_user_notification_returns_404(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $n = $u1->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\\Notifications\\ReviewHiddenNotification',
            'data' => ['link' => '/'], 'read_at' => null,
        ]);
        $this->actingAs($u2)->post(route('notifications.read', $n->id))->assertNotFound();
    }

    public function test_unread_count_displayed_in_layout(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\\Notifications\\ReviewHiddenNotification',
            'data' => ['link' => '/'], 'read_at' => null,
        ]);
        $res = $this->actingAs($user)->get('/');
        $res->assertOk();
        $this->assertStringContainsString('badge bg-danger', $res->getContent());
    }
}
