#!/usr/bin/env bash
# SolarShare — script d'installation (Linux / macOS / Git Bash)
# Périmètre du projet : Module 3 (Avis & signalements) + Auth/Utilisateurs.
set -euo pipefail

APP_DIR="${1:-solarshare}"

# 1. Squelette Laravel 12
composer create-project laravel/laravel:^12 "$APP_DIR" --no-interaction

# 2. Code SolarShare : module Avis & signalements + authentification/utilisateurs
cp -R app config database resources routes tests public "$APP_DIR/"
cp .env.example .editorconfig .gitattributes phpunit.xml vite.config.js "$APP_DIR/" 2>/dev/null || true

cd "$APP_DIR"
composer install --no-interaction --prefer-dist

# 3. Environnement + base de données + seed (avis, signalements, réservations terminées, équipements)
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

# 4. Vérification
php artisan test

echo "Prêt : php artisan serve  →  http://127.0.0.1:8000"
echo "Admin : admin@solarshare.tn / Admin1234"
