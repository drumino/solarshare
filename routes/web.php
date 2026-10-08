<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Front;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FRONT OFFICE
|--------------------------------------------------------------------------
*/
Route::get('/', [Front\HomeController::class, 'index'])->name('front.home');

// Authentification
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// MODULE 1 — Catalogue
Route::get('/equipements', [Front\EquipmentController::class, 'index'])->name('front.equipment.index');
Route::middleware('auth')->group(function () {
    Route::get('/equipements/proposer', [Front\EquipmentController::class, 'create'])->name('front.equipment.create');
    Route::post('/equipements', [Front\EquipmentController::class, 'store'])->name('front.equipment.store');
    Route::post('/ia/equipement/description', [Front\EquipmentController::class, 'aiDescription'])->name('front.ai.description');
    Route::post('/ia/equipement/prix', [Front\EquipmentController::class, 'aiPrice'])->name('front.ai.price');

    // MODULE — Disponibilité et maintenance (propriétaire)
    Route::get('/mes-equipements', [Front\MaintenanceController::class, 'index'])->name('front.maintenance.index');
    Route::get('/mes-equipements/{equipment}/maintenance', [Front\MaintenanceController::class, 'manage'])->name('front.maintenance.manage');
    Route::post('/mes-equipements/{equipment}/maintenance', [Front\MaintenanceController::class, 'store'])->name('front.maintenance.store');
    Route::put('/mes-equipements/{equipment}/maintenance/{record}', [Front\MaintenanceController::class, 'update'])->name('front.maintenance.update');
    Route::delete('/mes-equipements/{equipment}/maintenance/{record}', [Front\MaintenanceController::class, 'destroy'])->name('front.maintenance.destroy');
});
Route::get('/equipements/{equipment}', [Front\EquipmentController::class, 'show'])->name('front.equipment.show');

// MODULE UTILISATEURS — profil
Route::middleware('auth')->group(function () {
    Route::get('/mon-profil', [Front\ProfileController::class, 'edit'])->name('front.profile.edit');
    Route::put('/mon-profil', [Front\ProfileController::class, 'update'])->name('front.profile.update');
    Route::put('/mon-profil/mot-de-passe', [Front\ProfileController::class, 'updatePassword'])->name('front.profile.password');
});

/*
|--------------------------------------------------------------------------
| BACK OFFICE (administrateur)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // MODULE UTILISATEURS
    Route::resource('users', Admin\UserController::class)->except('show');

    // MODULE 1 — Catalogue
    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('equipment', Admin\EquipmentController::class)->except('show');

    // MODULE — Disponibilité et maintenance
    Route::get('maintenance', [Admin\MaintenanceController::class, 'index'])->name('maintenance.index');
});
