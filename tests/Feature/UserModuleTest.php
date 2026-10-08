<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_pages_are_accessible(): void
    {
        $this->get('/')->assertOk();
        $this->get('/connexion')->assertOk();
        $this->get('/inscription')->assertOk();
    }

    public function test_registration_creates_a_regular_user(): void
    {
        $this->post('/inscription', [
            'name' => 'Nouveau Membre', 'email' => 'nouveau@example.com', 'city' => 'Tunis',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nouveau@example.com', 'role' => 'user']);
    }

    public function test_regular_user_is_not_redirected_to_a_saved_admin_url_after_login(): void
    {
        $user = User::factory()->create(['password' => 'Password123']);

        $this->get('/admin')->assertRedirect('/connexion');
        $this->post('/connexion', ['email' => $user->email, 'password' => 'Password123'])
            ->assertRedirect('/');
    }

    public function test_admin_area_requires_admin_role(): void
    {
        $this->get('/admin')->assertRedirect('/connexion');

        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/users')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin')->assertOk();
        $this->get('/admin/users')->assertOk();
    }

    public function test_admin_can_create_update_and_delete_a_user(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/users', [
            'name' => 'Sami Ben Ali', 'email' => 'sami@example.com', 'role' => 'user',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertRedirect('/admin/users');
        $user = User::where('email', 'sami@example.com')->firstOrFail();

        $this->put("/admin/users/{$user->id}", [
            'name' => 'Sami Modifié', 'email' => 'sami@example.com', 'role' => 'admin', 'city' => 'Sfax',
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('Password123', $user->password), 'Un mot de passe vide conserve l\'actuel.');

        $this->delete("/admin/users/{$user->id}")->assertSessionHas('success');
        $this->assertModelMissing($user);
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'pris@example.com']);

        $this->actingAs($this->admin())->post('/admin/users', [
            'name' => 'Doublon', 'email' => 'pris@example.com', 'role' => 'user',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_delete_self_nor_demote_last_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->delete("/admin/users/{$admin->id}")->assertSessionHas('error');
        $this->assertModelExists($admin);

        $this->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'user',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->create(['password' => 'Password123']);
        $this->actingAs($user);

        $this->get('/mon-profil')->assertOk();
        $this->put('/mon-profil', ['name' => 'Leila Trabelsi', 'email' => 'leila@example.com', 'city' => 'Sousse'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Leila Trabelsi', 'city' => 'Sousse']);

        $this->put('/mon-profil/mot-de-passe', [
            'current_password' => 'mauvais123', 'password' => 'Nouveau1234', 'password_confirmation' => 'Nouveau1234',
        ])->assertSessionHasErrors('current_password');

        $this->put('/mon-profil/mot-de-passe', [
            'current_password' => 'Password123', 'password' => 'Nouveau1234', 'password_confirmation' => 'Nouveau1234',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nouveau1234', $user->fresh()->password));
    }
}
