<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
    }

    public function test_admin_receives_local_engine_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.help.ask'), [
            'question' => 'Comment filtrer les avis ?',
            'page' => 'avis',
        ]);

        $response->assertOk();
        $response->assertJson(['engine' => 'local']);
    }

    public function test_known_question_returns_matching_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.help.ask'), [
            'question' => 'Comment filtrer les avis par sentiment ou produit ?',
            'page' => 'avis',
        ])->assertJson([
            'answer' => 'Filtrez les avis par sentiment, visibilité ou produit.',
            'engine' => 'local',
        ]);
    }

    public function test_unknown_question_returns_default_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.help.ask'), [
            'question' => 'blablabla inconnu xyz',
            'page' => 'avis',
        ])->assertJson([
            'answer' => 'Je peux vous aider sur : la liste et la recherche des avis, les filtres (sentiment, visibilité, produit), l analyse IA, la création et la modification, le masquage et la suppression.',
            'engine' => 'local',
        ]);
    }

    public function test_empty_question_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.help.ask'), [
            'question' => '',
            'page' => 'avis',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post(route('admin.help.ask'), [
            'question' => 'Comment filtrer les avis ?',
            'page' => 'avis',
        ])->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->post(route('admin.help.ask'), [
            'question' => 'Comment filtrer les avis ?',
            'page' => 'avis',
        ])->assertRedirect();
    }
}
