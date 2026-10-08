<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Utilisateurs ----------------------------------------------------
        User::create(['name' => 'Administrateur SolarShare', 'email' => 'admin@solarshare.tn', 'password' => 'Admin1234', 'role' => 'admin', 'city' => 'Tunis']);
        $sami = User::create(['name' => 'Sami Ben Ali', 'email' => 'sami@example.com', 'password' => 'Password123', 'phone' => '+216 20 111 222', 'city' => 'Tunis']);
        $leila = User::create(['name' => 'Leila Trabelsi', 'email' => 'leila@example.com', 'password' => 'Password123', 'phone' => '+216 22 333 444', 'city' => 'Sousse']);
        $karim = User::create(['name' => 'Karim Jlassi', 'email' => 'karim@example.com', 'password' => 'Password123', 'phone' => '+216 98 555 666', 'city' => 'Sfax']);
        User::create(['name' => 'Ines Gharbi', 'email' => 'ines@example.com', 'password' => 'Password123', 'city' => 'Tunis']);

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

        $mk('panneaux-solaires-portables', $sami, 'Panneau pliable 100 W', 'solar_panel', 100, null, 6, 80, 'tres_bon', 'Tunis', 'available', 'Panneau pliable monocristallin 100 W, idéal pour le camping et la recharge de stations portables.');
        $mk('panneaux-solaires-portables', $leila, 'Panneau portable 200 W', 'solar_panel', 200, null, 11, 150, 'neuf', 'Sousse', 'available', 'Panneau 200 W avec béquilles et connecteurs MC4, rendement élevé.');
        $mk('batteries-stations', $karim, 'Station d\'énergie 500 Wh', 'battery', null, 500, 9, 120, 'bon', 'Sfax', 'available', 'Station portable 500 Wh : prises USB, 12 V et 220 V.');
        $mk('batteries-stations', $sami, 'Batterie LiFePO4 1000 Wh', 'battery', 1000, 1000, 16, 250, 'tres_bon', 'Tunis', 'available', 'Batterie lithium fer phosphate 1 kWh, longue durée de vie.');
        $mk('batteries-stations', $leila, 'Station d\'énergie 2000 Wh', 'battery', 2000, 2000, 28, 400, 'neuf', 'Sousse', 'available', 'Grosse capacité pour événements et chantiers.');
        $mk('eolien', $karim, 'Mini-éolienne 400 W', 'wind', 400, null, 14, 200, 'bon', 'Bizerte', 'available', 'Éolienne à axe horizontal avec régulateur de charge.');
        $mk('onduleurs-accessoires', $sami, 'Onduleur pur sinus 1000 W', 'inverter', 1000, null, 8, 100, 'tres_bon', 'Tunis', 'maintenance', 'Onduleur 12 V → 220 V pur sinus.');
        $mk('panneaux-solaires-portables', $karim, 'Panneau rigide 150 W', 'solar_panel', 150, null, 8, 100, 'usage', 'Sfax', 'available', 'Panneau rigide polycristallin, usage chantier.');
        $mk('batteries-stations', $leila, 'Batterie 300 Wh compacte', 'battery', null, 300, 6, 70, 'bon', 'Nabeul', 'pending', 'Petite batterie compacte pour smartphones et éclairage.');
    }
}
