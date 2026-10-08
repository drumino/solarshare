<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private function equipment(User $owner): Equipment
    {
        $category = Category::create(['name' => 'Batteries', 'slug' => 'batteries', 'icon' => 'bi-battery']);

        return Equipment::create([
            'category_id' => $category->id, 'owner_id' => $owner->id, 'title' => 'Station 500 Wh',
            'type' => 'battery', 'capacity_wh' => 500, 'price_per_day' => 10, 'condition' => 'bon',
            'city' => 'Tunis', 'status' => 'available',
        ]);
    }

    public function test_owner_can_schedule_a_maintenance_block(): void
    {
        $owner = User::factory()->create();
        $equipment = $this->equipment($owner);

        $this->actingAs($owner)->post(route('front.maintenance.store', $equipment), [
            'title' => 'Révision électrique', 'reason' => 'inspection', 'starts_on' => today()->toDateString(),
            'ends_on' => today()->addDays(2)->toDateString(), 'status' => 'scheduled', 'cost' => 35,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('maintenance_records', [
            'equipment_id' => $equipment->id, 'reported_by' => $owner->id, 'status' => 'scheduled',
        ]);
        // The owner retains access to manage an item, while other visitors do not.
        $this->get("/equipements/{$equipment->id}")->assertOk();
        $this->actingAs(User::factory()->create())->get("/equipements/{$equipment->id}")->assertNotFound();
        $this->get('/equipements')->assertDontSee('Station 500 Wh');
    }

    public function test_owner_cannot_create_overlapping_active_maintenance(): void
    {
        $owner = User::factory()->create();
        $equipment = $this->equipment($owner);
        MaintenanceRecord::create([
            'equipment_id' => $equipment->id, 'reported_by' => $owner->id, 'title' => 'Première réparation',
            'reason' => 'repair', 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-12', 'status' => 'scheduled',
        ]);

        $this->actingAs($owner)->post(route('front.maintenance.store', $equipment), [
            'title' => 'Seconde réparation', 'reason' => 'repair', 'starts_on' => '2026-02-12',
            'ends_on' => '2026-02-14', 'status' => 'in_progress',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('maintenance_records', 1);
    }

    public function test_only_equipment_owner_can_manage_maintenance(): void
    {
        $equipment = $this->equipment(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('front.maintenance.manage', $equipment))
            ->assertForbidden();
    }

    public function test_admin_can_oversee_maintenance_records(): void
    {
        $owner = User::factory()->create();
        $equipment = $this->equipment($owner);
        MaintenanceRecord::create([
            'equipment_id' => $equipment->id, 'reported_by' => $owner->id, 'title' => 'Contrôle annuel',
            'reason' => 'inspection', 'starts_on' => today(), 'ends_on' => today(), 'status' => 'completed',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/maintenance')->assertOk()->assertSee('Contrôle annuel');
    }
}
