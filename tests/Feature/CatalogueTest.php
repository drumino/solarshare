<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use App\Services\Ai\EquipmentAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']); // tests déterministes : moteur local
        $this->category = Category::create(['name' => 'Panneaux', 'slug' => 'panneaux', 'icon' => 'bi-sun']);
    }

    private function makeEquipment(User $owner, string $status = 'available', string $title = 'Panneau 100 W'): Equipment
    {
        return Equipment::create([
            'category_id' => $this->category->id, 'owner_id' => $owner->id, 'title' => $title, 'type' => 'solar_panel',
            'power_watts' => 100, 'price_per_day' => 6, 'deposit' => 50, 'condition' => 'bon', 'city' => 'Tunis', 'status' => $status,
        ]);
    }

    public function test_catalogue_lists_only_available_equipment(): void
    {
        $owner = User::factory()->create();
        $available = $this->makeEquipment($owner, 'available', 'Panneau disponible');
        $pending = $this->makeEquipment($owner, 'pending', 'Panneau en attente');

        $this->get('/equipements')->assertOk()->assertSee('Panneau disponible')->assertDontSee('Panneau en attente');
        $this->get("/equipements/{$available->id}")->assertOk();
        $this->get("/equipements/{$pending->id}")->assertNotFound();
        $this->actingAs($owner)->get("/equipements/{$pending->id}")->assertOk();
    }

    public function test_submitted_equipment_awaits_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/equipements', [
            'category_id' => $this->category->id, 'title' => 'Station 500 Wh', 'type' => 'battery',
            'capacity_wh' => 500, 'price_per_day' => 9, 'condition' => 'bon', 'city' => 'Sfax',
        ])->assertRedirect('/equipements');

        $this->assertDatabaseHas('equipment', ['title' => 'Station 500 Wh', 'owner_id' => $user->id, 'status' => 'pending']);
    }

    public function test_conditional_fields_depend_on_type(): void
    {
        $this->actingAs(User::factory()->create());
        $base = ['category_id' => $this->category->id, 'title' => 'Équipement test', 'price_per_day' => 5, 'condition' => 'bon', 'city' => 'Tunis'];

        $this->post('/equipements', $base + ['type' => 'solar_panel'])->assertSessionHasErrors('power_watts');
        $this->post('/equipements', $base + ['type' => 'battery'])->assertSessionHasErrors('capacity_wh');
    }

    public function test_admin_manages_categories_and_equipment(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/equipment')->assertOk();

        $this->post('/admin/categories', ['name' => 'Éolien', 'icon' => 'bi-wind'])->assertRedirect('/admin/categories');
        $this->assertDatabaseHas('categories', ['name' => 'Éolien', 'slug' => 'eolien']);

        $this->makeEquipment(User::factory()->create());
        $this->delete("/admin/categories/{$this->category->id}")->assertSessionHas('error');
        $this->assertModelExists($this->category);
    }

    public function test_catalogue_admin_requires_admin_role(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/equipment')->assertForbidden();
    }

    public function test_price_suggestion_uses_local_engine(): void
    {
        $result = app(EquipmentAssistant::class)->suggestPrice(['type' => 'solar_panel', 'power_watts' => 200, 'condition' => 'tres_bon']);

        $this->assertSame(6.0, $result['suggested']); // 200 W × 0,03, aucun prix de marché
        $this->assertSame('fallback', app(EquipmentAssistant::class)->describe(['type' => 'battery', 'capacity_wh' => 500])['source']);
    }
}
