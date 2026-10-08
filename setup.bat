@echo off
REM SolarShare - script d'installation (Windows)
REM Perimetre du projet : Module 3 (Avis & signalements) + Auth/Utilisateurs.
setlocal

set "APP_DIR=%~1"
if "%APP_DIR%"=="" set "APP_DIR=solarshare"

REM 1. Squelette Laravel 12
composer create-project laravel/laravel:^12 "%APP_DIR%" --no-interaction
if errorlevel 1 exit /b %errorlevel%

REM 2. Code SolarShare : module Avis & signalements + authentification/utilisateurs
xcopy /E /I /Y app "%APP_DIR%\app" >nul
xcopy /E /I /Y config "%APP_DIR%\config" >nul
xcopy /E /I /Y database "%APP_DIR%\database" >nul
xcopy /E /I /Y resources "%APP_DIR%\resources" >nul
xcopy /E /I /Y routes "%APP_DIR%\routes" >nul
xcopy /E /I /Y tests "%APP_DIR%\tests" >nul
xcopy /E /I /Y public "%APP_DIR%\public" >nul
copy /Y .env.example "%APP_DIR%\.env.example" >nul
copy /Y phpunit.xml "%APP_DIR%\phpunit.xml" >nul
copy /Y vite.config.js "%APP_DIR%\vite.config.js" >nul

cd "%APP_DIR%"
composer install --no-interaction --prefer-dist
if errorlevel 1 exit /b %errorlevel%

REM 3. Environnement + base de données + seed (avis, signalements, reservations terminees, equipements)
copy /Y .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

REM 4. Verification
php artisan test
if errorlevel 1 exit /b %errorlevel%

echo Pret : php artisan serve  -^>  http://127.0.0.1:8000
echo Admin : admin@solarshare.tn / Admin1234
