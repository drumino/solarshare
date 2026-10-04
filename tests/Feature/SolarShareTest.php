<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\IncidentTriage;
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
        $this->get('/equipements')->assertOk();
        $this->get('/assistant-energie')->assertOk();
    }

    public function test_admin_area_requires_admin_role(): void
    {
        $this->get('/admin')->assertRedirect('/connexion');

        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk();
    }

    public function test_overlapping_reservation_is_rejected(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $other = User::factory()->create();
        $equipment = $this->makeEquipment($owner);

        Reservation::create([
            'equipment_id' => $equipment->id, 'user_id' => $other->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5),
            'total_price' => 18, 'status' => 'confirmed',
        ]);

        $this->actingAs($renter)->post('/reservations', [
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
        ])->assertSessionHasErrors('start_date');

        $this->actingAs($renter)->post('/reservations', [
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', ['user_id' => $renter->id, 'total_price' => 12]);
    }

    public function test_owner_cannot_rent_own_equipment(): void
    {
        $owner = User::factory()->create();
        $equipment = $this->makeEquipment($owner);

        $this->actingAs($owner)->post('/reservations', [
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ])->assertSessionHasErrors('equipment_id');
    }

    public function test_energy_advisor_computes_daily_consumption(): void
    {
        $this->post('/assistant-energie', [
            'appliances' => [['name' => 'Ordinateur', 'watts' => 100, 'hours' => 3]],
            'days' => 2,
        ])->assertOk()->assertSee('300');
    }

    public function test_review_analyzer_flags_toxic_comment(): void
    {
        $result = app(ReviewAnalyzer::class)->analyze('Vendeur nul et idiot', 1);

        $this->assertTrue($result['toxic']);
        $this->assertSame('negative', $result['sentiment']);
    }

    public function test_incident_triage_detects_safety_issue(): void
    {
        $result = app(IncidentTriage::class)->triage('Problème', 'Beaucoup de fumée sort de la batterie pendant la charge.');

        $this->assertSame('safety', $result['category']);
        $this->assertSame('critical', $result['severity']);
    }
}
