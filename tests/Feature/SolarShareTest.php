<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
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
        $this->get('/equipements')->assertNotFound();
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
}
