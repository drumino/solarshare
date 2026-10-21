<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\ReviewRevision;
use App\Models\User;
use App\Notifications\ReviewHiddenNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewEditTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
        Carbon::setTestNow(null);
    }

    private function makeReview(User $author, string $comment = 'Excellent materiel, tres satisfait du service.', int $rating = 5, ?array $criteria = null): Review
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);
        $equipment = Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);

        return Review::create([
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => $rating,
            'comment' => $comment, 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
            'criteria_ratings' => $criteria, 'aspects' => [],
        ]);
    }

    public function test_author_can_edit_within_24h(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-11 09:00:00'); // 23h après creation

        $response = $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Toujours tres satisfait du materiel.',
            'rating' => 4,
        ]);

        $response->assertRedirect(route('front.home'));

        $review->refresh();
        $this->assertSame(4, $review->rating);
        $this->assertSame('Toujours tres satisfait du materiel.', $review->comment);
        $this->assertNotNull($review->edited_at);
        $this->assertDatabaseHas('review_revisions', [
            'review_id' => $review->id,
            'old_rating' => 5,
            'old_comment' => 'Excellent materiel, tres satisfait du service.',
            'old_sentiment' => 'positive',
        ]);
        $this->assertDatabaseCount('review_revisions', 1);
    }

    public function test_edit_refused_after_24h(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-11 11:00:00'); // 25h après creation

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Toujours tres satisfait du materiel.',
            'rating' => 4,
        ])->assertForbidden();

        $review->refresh();
        $this->assertSame(5, $review->rating);
        $this->assertDatabaseCount('review_revisions', 0);
    }

    public function test_other_user_cannot_edit(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $intruder = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-10 11:00:00'); // 1h apres

        $this->actingAs($intruder)->put(route('front.reviews.update', $review), [
            'comment' => 'Tentative de modification par un autre utilisateur.',
            'rating' => 1,
        ])->assertForbidden();

        $review->refresh();
        $this->assertSame(5, $review->rating);
        $this->assertDatabaseCount('review_revisions', 0);
    }

    public function test_hidden_review_cannot_be_edited(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $review->update(['is_visible' => false]);

        Carbon::setTestNow('2026-06-10 11:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Tentative de modification dun avis masque.',
            'rating' => 1,
        ])->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $review = $this->makeReview(User::factory()->create());

        $this->put(route('front.reviews.update', $review), [
            'comment' => 'Tentative de modification sans session.',
            'rating' => 1,
        ])->assertRedirect('/connexion');
    }

    public function test_revision_stores_old_values(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author, 'Commentaire initial.', 4, [
            'quality' => 4, 'price' => 4, 'punctuality' => 5, 'communication' => 4,
        ]);

        Carbon::setTestNow('2026-06-10 12:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Commentaire modifie, vraiment bien ce materiel.',
            'criteria_ratings' => ['quality' => 5, 'price' => 5, 'punctuality' => 5, 'communication' => 5],
        ])->assertRedirect(route('front.home'));

        $rev = ReviewRevision::where('review_id', $review->id)->first();
        $this->assertNotNull($rev);
        $this->assertSame(4, $rev->old_rating);
        $this->assertSame('Commentaire initial.', $rev->old_comment);
        $this->assertSame('positive', $rev->old_sentiment);
        $this->assertSame(['quality' => 4, 'price' => 4, 'punctuality' => 5, 'communication' => 4], $rev->old_criteria_ratings);
        $this->assertNotNull($rev->edited_at);
    }

    public function test_rating_recomputed_from_criteria(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author, 'Commentaire.', 5, [
            'quality' => 5, 'price' => 5, 'punctuality' => 5, 'communication' => 5,
        ]);

        Carbon::setTestNow('2026-06-10 12:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Nouveau commentaire suffisamment long pour valider.',
            'criteria_ratings' => ['quality' => 1, 'price' => 1, 'punctuality' => 1, 'communication' => 1],
        ])->assertRedirect(route('front.home'));

        $this->assertSame(1, $review->fresh()->rating);
    }

    public function test_toxic_edit_hides_review_and_notifies_author(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-10 12:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Cest un escroc, ce materiel est de la merde.',
            'rating' => 1,
        ])->assertRedirect(route('front.home'));

        $this->assertFalse($review->fresh()->is_visible);
        Notification::assertSentTo($author, ReviewHiddenNotification::class);
        $this->assertDatabaseCount('review_revisions', 1);
    }

    public function test_edited_at_and_modified_shown_on_home(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-11 09:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Mise a jour de mon avis sur ce materiel, toujours bien.',
            'rating' => 4,
        ])->assertRedirect(route('front.home'));

        $this->assertNotNull($review->fresh()->edited_at);

        $content = $this->get('/')->getContent();
        $this->assertStringContainsString('modifié', $content);
    }

    public function test_total_photos_never_exceed_three(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        $dir = 'reviews/'.$review->id;
        foreach (['a.png', 'b.png'] as $name) {
            $path = $dir.'/'.$name;
            Storage::disk('public')->put($path, base64_decode(self::PNG_1X1));
            ReviewPhoto::create(['review_id' => $review->id, 'path' => $path]);
        }

        Carbon::setTestNow('2026-06-10 11:00:00');

        $response = $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Commentaire mis a jour avec photos.',
            'rating' => 5,
            'photos' => [UploadedFile::fake()->createWithContent('c.png', base64_decode(self::PNG_1X1)), UploadedFile::fake()->createWithContent('d.png', base64_decode(self::PNG_1X1))],
        ]);

        $response->assertSessionHasErrors('photos');
        $this->assertSame(2, $review->fresh()->photos()->count());
        $this->assertDatabaseCount('review_revisions', 0);

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Encore une mise a jour du commentaire, toujours dans les limites.',
            'rating' => 5,
            'photos' => [UploadedFile::fake()->createWithContent('c.png', base64_decode(self::PNG_1X1))],
        ])->assertRedirect(route('front.home'));

        $this->assertSame(3, $review->fresh()->photos()->count());
    }

    public function test_admin_sees_revision_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();

        Carbon::setTestNow('2026-06-10 10:00:00');
        $review = $this->makeReview($author, 'Version originale du commentaire.', 4);

        Carbon::setTestNow('2026-06-10 14:00:00');
        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Version modifiee du commentaire, plus precise.',
            'rating' => 5,
        ])->assertRedirect(route('front.home'));

        $content = $this->actingAs($admin)->get(route('admin.reviews.edit', $review))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Version originale du commentaire.', $content);
        $this->assertStringContainsString('4/5', $content);
        $this->assertStringContainsString('10/06/2026', $content);
    }

    public function test_edit_form_loads_for_author(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        Carbon::setTestNow('2026-06-10 11:00:00');

        $this->actingAs($author)->get(route('front.reviews.edit', $review))
            ->assertOk()
            ->assertSee('Modifier votre avis')
            ->assertSee('Enregistrer les modifications')
            ->assertSee('Excellent materiel, tres satisfait du service.');
    }

    public function test_update_invalidates_criteria_averages_cache(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $author = User::factory()->create();
        $review = $this->makeReview($author);

        $key = 'equipment.'.$review->equipment_id.'_average_rating';
        $key2 = 'equipment.'.$review->equipment_id.'_criteria_averages';
        Cache::put($key, 4.5);
        Cache::put($key2, ['quality' => 4]);

        Carbon::setTestNow('2026-06-10 11:00:00');

        $this->actingAs($author)->put(route('front.reviews.update', $review), [
            'comment' => 'Un petit commentaire mis a jour, merci beaucoup.',
            'rating' => 4,
        ])->assertRedirect(route('front.home'));

        $this->assertNull(Cache::get($key));
        $this->assertNull(Cache::get($key2));
    }
}
