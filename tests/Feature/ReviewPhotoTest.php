<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewPhotoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
        Storage::fake('public');
    }

    private function makeEquipment(): Equipment
    {
        $owner = User::factory()->create();
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);

        return Equipment::create([
            'category_id' => $cat->id, 'owner_id' => $owner->id, 'title' => 'Panneau', 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => 'available',
        ]);
    }

    private function makeReservation(User $user): Reservation
    {
        return Reservation::create([
            'equipment_id' => $this->makeEquipment()->id,
            'user_id' => $user->id,
            'start_date' => now()->subDays(6)->toDateString(),
            'end_date' => now()->subDays(4)->toDateString(),
            'total_price' => 120,
            'status' => 'completed',
        ]);
    }

    private function makeReview(User $author, array $overrides = []): Review
    {
        return Review::create(array_merge([
            'equipment_id' => $this->makeEquipment()->id, 'user_id' => $author->id, 'rating' => 5,
            'comment' => 'Excellent materiel, tres satisfait du service.', 'sentiment' => 'positive',
            'sentiment_score' => 1, 'is_visible' => true, 'aspects' => [],
        ], $overrides));
    }

    private function attachPhoto(Review $review, string $name = 'photo.png'): ReviewPhoto
    {
        $path = 'reviews/'.$review->id.'/'.$name;
        Storage::disk('public')->put($path, 'binary-content');

        return ReviewPhoto::create(['review_id' => $review->id, 'path' => $path]);
    }

    private function imageFile(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG_1X1));
    }

    private function storePayload(array $photos = []): array
    {
        $payload = [
            'rating' => 5,
            'comment' => 'Excellent materiel, tres satisfait du service rendu.',
        ];

        if ($photos !== []) {
            $payload['photos'] = $photos;
        }

        return $payload;
    }

    public function test_upload_ok(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $response = $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload([
            $this->imageFile('panneau-un.png'),
            $this->imageFile('panneau-deux.png'),
        ]));

        $response->assertRedirect(route('front.home'));

        $review = Review::first();
        $this->assertNotNull($review);
        $this->assertSame(2, $review->photos()->count());
        $this->assertDatabaseCount('review_photos', 2);

        $files = Storage::disk('public')->allFiles('reviews/'.$review->id);
        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertStringStartsWith('reviews/'.$review->id.'/', $file);
            $this->assertMatchesRegularExpression('#^reviews/'.preg_quote((string) $review->id, '#').'/[A-Za-z0-9]{40}\.png$#', $file);
        }

        $names = array_map('basename', $files);
        $this->assertNotContains('panneau-un.png', $names);
        $this->assertNotContains('panneau-deux.png', $names);
        $this->assertCount(2, array_unique($names));
    }

    public function test_fourth_photo_rejected(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $response = $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload([
            $this->imageFile('a.png'),
            $this->imageFile('b.png'),
            $this->imageFile('c.png'),
            $this->imageFile('d.png'),
        ]));

        $response->assertSessionHasErrors('photos');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('reviews'));
    }

    public function test_non_image_rejected(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $response = $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload([
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]));

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('reviews'));
    }

    public function test_file_over_2mb_rejected(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $tooBig = UploadedFile::fake()->create('panneau.png', 100)->size(3000);

        $response = $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload([$tooBig]));

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('reviews'));
    }

    public function test_review_without_photos_still_works(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $response = $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload());

        $response->assertRedirect(route('front.home'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('review_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('reviews'));
    }

    public function test_hidden_review_photos_not_shown_publicly(): void
    {
        $author = User::factory()->create();
        $visible = $this->makeReview($author);
        $visiblePhoto = $this->attachPhoto($visible, 'visible.png');
        $hidden = $this->makeReview($author, ['is_visible' => false]);
        $hiddenPhoto = $this->attachPhoto($hidden, 'hidden.png');

        $content = $this->get('/')->getContent();

        $this->assertStringContainsString($visiblePhoto->path, $content);
        $this->assertStringNotContainsString($hiddenPhoto->path, $content);
        $this->assertStringNotContainsString('hidden.png', $content);
    }

    public function test_soft_delete_keeps_files(): void
    {
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'conservee.png');

        $review->delete();

        $this->assertTrue(Storage::disk('public')->exists($photo->path));
        $this->assertDatabaseHas('review_photos', ['id' => $photo->id]);
    }

    public function test_force_delete_removes_files(): void
    {
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'supprimee.png');

        $review->forceDelete();

        $this->assertFalse(Storage::disk('public')->exists($photo->path));
        $this->assertDatabaseMissing('review_photos', ['id' => $photo->id]);
    }

    public function test_admin_can_delete_photo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'admin.png');

        $response = $this->actingAs($admin)->delete(route('admin.reviews.photos.destroy', [$review, $photo]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('review_photos', ['id' => $photo->id]);
        $this->assertFalse(Storage::disk('public')->exists($photo->path));
    }

    public function test_admin_edit_view_shows_photos_with_delete_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'vue.png');

        $content = $this->actingAs($admin)->get(route('admin.reviews.edit', $review))
            ->assertOk()->getContent();

        $this->assertStringContainsString($photo->path, $content);
        $this->assertStringContainsString('Supprimer la photo', $content);
        $this->assertStringContainsString(route('admin.reviews.photos.destroy', [$review, $photo]), $content);
        $this->assertStringContainsString('name="_method" value="DELETE"', $content);
    }

    public function test_admin_index_shows_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'index.png');

        $content = $this->actingAs($admin)->get(route('admin.reviews.index'))
            ->assertOk()->getContent();

        $this->assertStringContainsString($photo->path, $content);
        $this->assertStringContainsString(route('admin.reviews.photos.destroy', [$review, $photo]), $content);
    }

    public function test_non_admin_cannot_delete_photo(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $photo = $this->attachPhoto($review, 'protege.png');

        $this->actingAs($user)->delete(route('admin.reviews.photos.destroy', [$review, $photo]))
            ->assertForbidden();

        $this->assertDatabaseHas('review_photos', ['id' => $photo->id]);
        $this->assertTrue(Storage::disk('public')->exists($photo->path));
    }

    public function test_photo_of_another_review_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $other = $this->makeReview($author);
        $photo = $this->attachPhoto($other, 'autre.png');

        $this->actingAs($admin)->delete(route('admin.reviews.photos.destroy', [$review, $photo]))
            ->assertNotFound();

        $this->assertDatabaseHas('review_photos', ['id' => $photo->id]);
        $this->assertTrue(Storage::disk('public')->exists($photo->path));
    }

    public function test_files_deleted_when_store_fails(): void
    {
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        ReviewPhoto::creating(function () {
            throw new \RuntimeException('echec du stockage photo');
        });

        $this->actingAs($user)->post(route('front.reviews.store', $reservation), $this->storePayload([
            $this->imageFile('echec.png'),
        ]));

        $this->assertSame([], Storage::disk('public')->allFiles('reviews'));
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_photos', 0);
    }

    public function test_home_has_no_n_plus_one_with_photos(): void
    {
        $author = User::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            $this->attachPhoto($this->makeReview($author));
        }

        DB::enableQueryLog();
        $this->get('/');
        $q1 = count(DB::getQueryLog());

        for ($i = 0; $i < 12; $i++) {
            $this->attachPhoto($this->makeReview($author));
        }

        DB::flushQueryLog();
        $this->get('/');
        $q2 = count(DB::getQueryLog());

        $this->assertLessThanOrEqual($q1 + 2, $q2);
    }
}
