<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use App\Models\User;
use App\Services\EquipmentRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EquipmentRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['solarshare.ai.driver' => 'none']);
    }

    private function makeOwner(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeEquipment(User $owner, array $overrides = []): Equipment
    {
        $cat = Category::firstOrCreate(['slug' => 'panneaux'], ['name' => 'Panneaux', 'icon' => 'bi-sun']);

        return Equipment::create(array_merge([
            'category_id' => $cat->id,
            'owner_id' => $owner->id,
            'title' => 'Panneau test',
            'type' => 'solar_panel',
            'power_watts' => 100,
            'price_per_day' => 6,
            'deposit' => 50,
            'condition' => 'bon',
            'city' => 'Tunis',
            'status' => 'available',
        ], $overrides));
    }

    private function addReview(Equipment $equipment, User $user, int $rating, bool $visible = true): Review
    {
        return Review::create([
            'equipment_id' => $equipment->id,
            'user_id' => $user->id,
            'rating' => $rating,
            'comment' => 'Commentaire de test.',
            'sentiment' => 'neutral',
            'sentiment_score' => 0,
            'is_visible' => $visible,
        ]);
    }

    public function test_neuf_avis_ne_donne_pas_le_badge_top_rated(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner);
        $user = User::factory()->create();

        foreach (range(1, 9) as $i) {
            $this->addReview($equipment, $user, 5);
        }

        $equipment->refresh();
        $this->assertSame(9, $equipment->reviews_count);
        $this->assertSame('5.00', $equipment->reviews_avg);
        $this->assertFalse($equipment->isTopRated());
        $this->assertFalse($equipment->is_top_rated);
    }

    public function test_dix_avis_a_quatre_point_cinq_donne_le_badge_top_rated(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner);
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->addReview($equipment, $user, 5);
            $this->addReview($equipment, $user, 4);
        }

        $equipment->refresh();
        $this->assertSame(10, $equipment->reviews_count);
        $this->assertSame('4.50', $equipment->reviews_avg);
        $this->assertTrue($equipment->isTopRated());
    }

    public function test_masquer_un_avis_retire_le_badge_top_rated(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner);
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->addReview($equipment, $user, 5);
            $this->addReview($equipment, $user, 4);
        }

        $this->assertTrue($equipment->fresh()->isTopRated());

        $review = $equipment->reviews()->first();
        $review->hide('Test masquage');

        $this->assertFalse($equipment->fresh()->isTopRated());
        $this->assertSame(9, $equipment->fresh()->reviews_count);
    }

    public function test_soft_delete_met_a_jour_le_compte_d_avis(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner);
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->addReview($equipment, $user, 5);
            $this->addReview($equipment, $user, 4);
        }

        $this->assertTrue($equipment->fresh()->isTopRated());

        $review = $equipment->reviews()->first();
        $review->delete();

        $this->assertFalse($equipment->fresh()->isTopRated());
        $this->assertSame(9, $equipment->fresh()->reviews_count);
        $this->assertNotNull($review->fresh()->deleted_at);
    }

    public function test_score_bayesien_une_etoile_solitaire_rang_inf_trente_avis(): void
    {
        $owner = $this->makeOwner();
        $a = $this->makeEquipment($owner, ['title' => 'Equipement A']);
        $b = $this->makeEquipment($owner, ['title' => 'Equipement B']);
        $c = $this->makeEquipment($owner, ['title' => 'Equipement C']);
        $user = User::factory()->create();

        // A : 30 avis moyennes 4.6 (18x5 + 12x4 = 138)
        foreach (range(1, 18) as $i) {
            $this->addReview($a, $user, 5);
        }
        foreach (range(1, 12) as $i) {
            $this->addReview($a, $user, 4);
        }

        // B : 1 avis a 5
        $this->addReview($b, $user, 5);

        // C : tire la moyenne globale vers le bas (20x3 = 60)
        foreach (range(1, 20) as $i) {
            $this->addReview($c, $user, 3);
        }

        foreach ([$a, $b, $c] as $eq) {
            app(EquipmentRatingService::class)->recompute($eq);
        }

        $a->refresh();
        $b->refresh();

        $this->assertGreaterThan(0, $a->bayesian_score);
        $this->assertGreaterThan(0, $b->bayesian_score);
        $this->assertLessThan($a->bayesian_score, $b->bayesian_score,
            'Une seule note 5/5 doit etre portee par le score bayesien en dessous de 30 avis a 4.6.');
    }

    public function test_methode_bayesien_est_pure(): void
    {
        $score = EquipmentRatingService::bayesian(5.0, 1, 4.6, 5);
        $this->assertSame(4.667, round($score, 3));
    }

    public function test_listing_trie_mieux_notes_par_score_bayesien_desc(): void
    {
        $owner = $this->makeOwner();
        $user = User::factory()->create();

        $haut = $this->makeEquipment($owner, ['title' => 'Haut score']);
        $bas = $this->makeEquipment($owner, ['title' => 'Bas score']);

        foreach (range(1, 30) as $i) {
            $this->addReview($haut, $user, 5);
        }
        $this->addReview($bas, $user, 3);

        app(EquipmentRatingService::class)->recompute($haut);
        app(EquipmentRatingService::class)->recompute($bas);

        $response = $this->get(route('front.equipements.index', ['tri' => 'mieux_notes']));
        $ids = $response->viewData('equipments')->pluck('id')->toArray();
        $response->assertOk();

        $ids = $response->viewData('equipments')->pluck('id')->toArray();
        $ids = $response->viewData('equipments')->pluck('id')->toArray();
        $this->assertContains($haut->id, $ids);
        $this->assertContains($bas->id, $ids);
        $this->assertTrue(array_search($haut->id, $ids) < array_search($bas->id, $ids));
    }

    public function test_filtre_top_rated(): void
    {
        $owner = $this->makeOwner();
        $user = User::factory()->create();

        $bon = $this->makeEquipment($owner, ['title' => 'Equipement Top']);
        $mauvais = $this->makeEquipment($owner, ['title' => 'Equipement Pas Top']);

        foreach (range(1, 5) as $i) {
            $this->addReview($bon, $user, 5);
            $this->addReview($bon, $user, 4);
        }
        $this->addReview($mauvais, $user, 3);

        app(EquipmentRatingService::class)->recompute($bon);
        app(EquipmentRatingService::class)->recompute($mauvais);

        $response = $this->get(route('front.equipements.index', ['top' => 1]));
        $response->assertOk();

        $ids = $response->viewData('equipments')->pluck('id')->toArray();
        $this->assertContains($bon->id, $ids);
        $this->assertNotContains($mauvais->id, $ids);
    }

    public function test_commande_recompute_scores_met_a_jour_tous_les_equipements(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner);
        $user = User::factory()->create();

        foreach (range(1, 10) as $i) {
            $this->addReview($equipment, $user, 5);
        }

        $exit = Artisan::call('reviews:recompute-scores');
        $this->assertSame(0, $exit);

        $this->assertSame(10, $equipment->fresh()->reviews_count);
        $this->assertSame('5.00', $equipment->fresh()->reviews_avg);
        $this->assertTrue($equipment->fresh()->isTopRated());
    }

    public function test_tri_invalide_retourne_au_defaut_sans_erreur(): void
    {
        $response = $this->get(route('front.equipements.index', ['tri' => 'n_import_quoi']));
        $response->assertOk();

        $tri = $response->viewData('tri');
        $this->assertSame('recents', $tri);
    }

    public function test_badge_affiche_sur_page_detail(): void
    {
        $owner = $this->makeOwner();
        $equipment = $this->makeEquipment($owner, ['title' => 'Detail Top']);
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->addReview($equipment, $user, 5);
            $this->addReview($equipment, $user, 4);
        }

        app(EquipmentRatingService::class)->recompute($equipment);

        $response = $this->get(route('front.equipements.show', $equipment));
        $response->assertOk();
        $response->assertSee('Top Rated');
    }
}
