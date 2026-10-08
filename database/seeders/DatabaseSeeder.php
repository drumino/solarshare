<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Utilisateurs ----------------------------------------------------
        User::create(['name' => 'Administrateur SolarShare', 'email' => 'admin@solarshare.tn', 'password' => 'Admin1234', 'role' => 'admin', 'city' => 'Tunis']);
        User::create(['name' => 'Sami Ben Ali', 'email' => 'sami@example.com', 'password' => 'Password123', 'phone' => '+216 20 111 222', 'city' => 'Tunis']);
        User::create(['name' => 'Leila Trabelsi', 'email' => 'leila@example.com', 'password' => 'Password123', 'phone' => '+216 22 333 444', 'city' => 'Sousse']);
        User::create(['name' => 'Karim Jlassi', 'email' => 'karim@example.com', 'password' => 'Password123', 'phone' => '+216 98 555 666', 'city' => 'Sfax']);
        User::create(['name' => 'Ines Gharbi', 'email' => 'ines@example.com', 'password' => 'Password123', 'city' => 'Tunis']);
    }
}
