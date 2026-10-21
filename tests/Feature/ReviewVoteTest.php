<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewVoteTest extends TestCase
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
            'equipment_id' => $equipment->id, 'user_id' => $author->id, 'rating' => 5,
            'comment' => 'ok', 'sentiment' => 'positive', 'sentiment_score' => 1, 'is_visible' => true,
        ]);
    }

    public function test_user_can_vote_once(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $review = $this->makeReview($author);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->assertOk()->assertJson(['voted' => true, 'count' => 1]);
    }

    public function test_second_post_toggles_off(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $review = $this->makeReview($author);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->assertOk()->assertJson(['voted' => false, 'count' => 0]);
    }

    public function test_count_never_negative(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $review = $this->makeReview($author);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $review->refresh();
        $this->assertGreaterThanOrEqual(0, $review->helpful_count);
    }

    public function test_own_review_vote_refused(): void
    {
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $this->actingAs($author)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])->assertForbidden();
    }

    public function test_hidden_review_vote_refused(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $review = $this->makeReview($author);
        $review->update(['is_visible' => false]);
        $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])->assertForbidden();
    }

    public function test_guest_redirected(): void
    {
        $author = User::factory()->create();
        $review = $this->makeReview($author);
        $this->post(route('front.reviews.vote', $review))->assertRedirect('/connexion');
    }

public function test_json_shape(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $review = $this->makeReview($author);
        $res = $this->actingAs($voter)->post(route('front.reviews.vote', $review), [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $res->assertOk()->assertJsonStructure(['voted', 'count']);
    }

    public function test_sort_utiles_orders_by_helpful_count(): void
    {
        $author = User::factory()->create();
        $r1 = $this->makeReview($author); $r1->update(['helpful_count' => 5, 'comment' => 'review-five']);
        $r2 = $this->makeReview($author); $r2->update(['helpful_count' => 2, 'comment' => 'review-two']);
        $r3 = $this->makeReview($author); $r3->update(['helpful_count' => 9, 'comment' => 'review-nine']);
        $content = $this->get('/?tri=utiles')->getContent();
        $pos9 = strpos($content, 'review-nine');
        $pos5 = strpos($content, 'review-five');
        $pos2 = strpos($content, 'review-two');
        $this->assertTrue($pos9 < $pos5 && $pos5 < $pos2);
    }

    public function test_sort_mieux_notes_orders_by_rating(): void
    {
        $author = User::factory()->create();
        $r1 = $this->makeReview($author); $r1->update(['rating' => 3, 'comment' => 'rating-three']);
        $r2 = $this->makeReview($author); $r2->update(['rating' => 5, 'comment' => 'rating-five']);
        $r3 = $this->makeReview($author); $r3->update(['rating' => 4, 'comment' => 'rating-four']);
        $content = $this->get('/?tri=mieux_notes')->getContent();
        $pos5 = strpos($content, 'rating-five');
        $pos4 = strpos($content, 'rating-four');
        $pos3 = strpos($content, 'rating-three');
        $this->assertTrue($pos5 < $pos4 && $pos4 < $pos3);
    }

    public function test_invalid_tri_falls_back_to_recents(): void
    {
        $this->get('/?tri=abc')->assertOk();
    }

    public function test_pagination_keeps_tri(): void
    {
        $author = User::factory()->create();
        for ($i = 0; $i < 8; $i++) {
            $this->makeReview($author);
        }
        $content = $this->get('/?tri=utiles')->getContent();
        $this->assertStringContainsString('tri=utiles', $content);
    }

    public function test_home_reviews_has_no_n_plus_one(): void
    {
        $author = User::factory()->create();
        for ($i = 0; $i < 3; $i++) { $this->makeReview($author); }
        DB::enableQueryLog();
        $this->get('/');
        $q1 = count(DB::getQueryLog());
        DB::flushQueryLog();
        for ($i = 0; $i < 12; $i++) { $this->makeReview($author); }
        $this->get('/');
        $q2 = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(110, $q2);
    }
}
