<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Utilisateurs ----------------------------------------------------
        $admin = User::create(['name' => 'Administrateur SolarShare', 'email' => 'admin@solarshare.tn', 'password' => 'Admin1234', 'role' => 'admin', 'city' => 'Tunis']);
        $sami = User::create(['name' => 'Sami Ben Ali', 'email' => 'sami@example.com', 'password' => 'Password123', 'phone' => '+216 20 111 222', 'city' => 'Tunis']);
        $leila = User::create(['name' => 'Leila Trabelsi', 'email' => 'leila@example.com', 'password' => 'Password123', 'phone' => '+216 22 333 444', 'city' => 'Sousse']);
        $karim = User::create(['name' => 'Karim Jlassi', 'email' => 'karim@example.com', 'password' => 'Password123', 'phone' => '+216 98 555 666', 'city' => 'Sfax']);

        // ---- Catégories ------------------------------------------------------
        $cat = [];
        foreach ([
            ['Panneaux solaires portables', 'panneaux-solaires-portables', 'bi-sun', 'Panneaux pliables et mobiles pour le camping, les chantiers et les coupures.'],
            ['Batteries & stations', 'batteries-stations', 'bi-battery-charging', 'Stockage d\'énergie et stations portables.'],
            ['Éolien', 'eolien', 'bi-wind', 'Petites éoliennes pour sites ventés.'],
            ['Onduleurs & accessoires', 'onduleurs-accessoires', 'bi-plug', 'Onduleurs, régulateurs et câblage.'],
        ] as [$name, $slug, $icon, $desc]) {
            $cat[$slug] = Category::create(compact('name', 'slug', 'icon') + ['description' => $desc]);
        }

        // ---- Équipements -----------------------------------------------------
        $mk = fn ($c, $owner, $title, $type, $w, $wh, $price, $dep, $cond, $city, $status, $desc) => Equipment::create([
            'category_id' => $cat[$c]->id, 'owner_id' => $owner->id, 'title' => $title, 'type' => $type,
            'power_watts' => $w, 'capacity_wh' => $wh, 'price_per_day' => $price, 'deposit' => $dep,
            'condition' => $cond, 'city' => $city, 'status' => $status, 'description' => $desc,
        ]);

        $e1 = $mk('panneaux-solaires-portables', $sami, 'Panneau pliable 100 W', 'solar_panel', 100, null, 6, 80, 'tres_bon', 'Tunis', 'available', 'Panneau pliable monocristallin 100 W, idéal pour le camping et la recharge de stations portables.');
        $e2 = $mk('panneaux-solaires-portables', $leila, 'Panneau portable 200 W', 'solar_panel', 200, null, 11, 150, 'neuf', 'Sousse', 'available', 'Panneau 200 W avec béquilles et connecteurs MC4, rendement élevé.');
        $e3 = $mk('batteries-stations', $karim, 'Station d\'énergie 500 Wh', 'battery', null, 500, 9, 120, 'bon', 'Sfax', 'available', 'Station portable 500 Wh : prises USB, 12 V et 220 V.');
        $e4 = $mk('batteries-stations', $sami, 'Batterie LiFePO4 1000 Wh', 'battery', 1000, 1000, 16, 250, 'tres_bon', 'Tunis', 'available', 'Batterie lithium fer phosphate 1 kWh, longue durée de vie.');
        $e5 = $mk('batteries-stations', $leila, 'Station d\'énergie 2000 Wh', 'battery', 2000, 2000, 28, 400, 'neuf', 'Sousse', 'available', 'Grosse capacité pour événements et chantiers.');
        $e6 = $mk('eolien', $karim, 'Mini-éolienne 400 W', 'wind', 400, null, 14, 200, 'bon', 'Bizerte', 'available', 'Éolienne à axe horizontal avec régulateur de charge.');
        $e7 = $mk('onduleurs-accessoires', $sami, 'Onduleur pur sinus 1000 W', 'inverter', 1000, null, 8, 100, 'tres_bon', 'Tunis', 'maintenance', 'Onduleur 12 V → 220 V pur sinus.');
        $e8 = $mk('panneaux-solaires-portables', $karim, 'Panneau rigide 150 W', 'solar_panel', 150, null, 8, 100, 'usage', 'Sfax', 'available', 'Panneau rigide polycristallin, usage chantier.');
        $e9 = $mk('batteries-stations', $leila, 'Batterie 300 Wh compacte', 'battery', null, 300, 6, 70, 'bon', 'Nabeul', 'pending', 'Petite batterie compacte pour smartphones et éclairage.');

        // ---- Réservations (terminées = base des avis) --------------------
        $book = function (Equipment $e, User $u, int $startOffset, int $days, string $status) {
            $start = now()->addDays($startOffset)->startOfDay();

            return Reservation::create([
                'equipment_id' => $e->id, 'user_id' => $u->id,
                'start_date' => $start, 'end_date' => $start->copy()->addDays($days - 1),
                'total_price' => $days * $e->price_per_day, 'status' => $status,
            ]);
        };

        $r1 = $book($e1, $leila, -20, 3, 'completed');
        $r2 = $book($e3, $sami, -15, 2, 'completed');
        $r3 = $book($e4, $karim, -12, 4, 'completed');
        $r4 = $book($e2, $sami, -8, 5, 'completed');
        $r5 = $book($e6, $leila, -3, 6, 'ongoing');
        $r6 = $book($e5, $sami, 5, 3, 'confirmed');
        $r7 = $book($e1, $karim, 7, 2, 'pending');
        $r8 = $book($e8, $leila, 10, 3, 'cancelled');
        $r9 = $book($e3, $leila, 12, 2, 'confirmed');

        // ---- Avis ------------------------------------------------------------
        $review = fn (Reservation $r, int $rating, string $comment, string $sent, float $score, bool $visible = true, ?string $note = null) => Review::create([
            'reservation_id' => $r->id, 'equipment_id' => $r->equipment_id, 'user_id' => $r->user_id,
            'rating' => $rating, 'comment' => $comment, 'sentiment' => $sent, 'sentiment_score' => $score,
            'is_visible' => $visible, 'moderation_note' => $note,
        ]);

        $v1 = $review($r1, 5, 'Excellent panneau, très efficace pendant notre week-end de camping. Propriétaire fiable, je recommande.', 'positive', 0.9);
        $v2 = $review($r2, 4, 'Station très pratique, un peu lourde mais la batterie tient bien. Merci !', 'positive', 0.6);
        $v3 = $review($r3, 2, 'Batterie décevante : elle ne tenait pas la charge annoncée et le câble était abîmé.', 'negative', -0.6);
        $v4 = $review($r4, 1, 'Vendeur nul et idiot, ne louez pas chez lui !', 'negative', -0.9, false, 'Langage inapproprié détecté');

        ReviewReport::create(['review_id' => $v2->id, 'user_id' => $karim->id, 'reason' => 'fake', 'details' => 'Je pense que cet avis est complaisant.', 'status' => 'pending']);
        ReviewReport::create(['review_id' => $v4->id, 'user_id' => $sami->id, 'reason' => 'offensive', 'details' => null, 'status' => 'reviewed']);
    }
}
