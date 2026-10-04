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

// Authentification (tâche commune)
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
});
Route::get('/equipements/{equipment}', [Front\EquipmentController::class, 'show'])->name('front.equipment.show');

// MODULE 2 — Réservations, paiements, assistant énergie
Route::get('/assistant-energie', [Front\EnergyAdvisorController::class, 'index'])->name('front.advisor');
Route::post('/assistant-energie', [Front\EnergyAdvisorController::class, 'analyze'])->name('front.advisor.analyze');
Route::middleware('auth')->group(function () {
    Route::get('/mes-reservations', [Front\ReservationController::class, 'index'])->name('front.reservations.index');
    Route::post('/reservations', [Front\ReservationController::class, 'store'])->name('front.reservations.store');
    Route::get('/reservations/{reservation}', [Front\ReservationController::class, 'show'])->name('front.reservations.show');
    Route::post('/reservations/{reservation}/annuler', [Front\ReservationController::class, 'cancel'])->name('front.reservations.cancel');
    Route::post('/reservations/{reservation}/payer', [Front\ReservationController::class, 'pay'])->name('front.reservations.pay');

    // MODULE 3 — Avis & signalements
    Route::get('/reservations/{reservation}/avis', [Front\ReviewController::class, 'create'])->name('front.reviews.create');
    Route::post('/reservations/{reservation}/avis', [Front\ReviewController::class, 'store'])->name('front.reviews.store');
    Route::post('/avis/{review}/signaler', [Front\ReviewController::class, 'report'])->name('front.reviews.report');

    // MODULE 4 — Incidents & maintenance
    Route::get('/mes-incidents', [Front\IncidentController::class, 'index'])->name('front.incidents.index');
    Route::get('/incidents/nouveau', [Front\IncidentController::class, 'create'])->name('front.incidents.create');
    Route::post('/incidents', [Front\IncidentController::class, 'store'])->name('front.incidents.store');
    Route::post('/ia/incident/triage', [Front\IncidentController::class, 'triage'])->name('front.incidents.triage');
});

/*
|--------------------------------------------------------------------------
| BACK OFFICE (administrateur)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Module 1
    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('equipment', Admin\EquipmentController::class)->except('show');

    // Module 2
    Route::resource('reservations', Admin\ReservationController::class)->except('show');
    Route::resource('payments', Admin\PaymentController::class)->except('show');

    // Module 3
    Route::resource('reviews', Admin\ReviewController::class)->except('show');
    Route::resource('review-reports', Admin\ReviewReportController::class)->except('show')
        ->parameters(['review-reports' => 'reviewReport']);

    // Module 4
    Route::resource('incidents', Admin\IncidentController::class)->except('show');
    Route::resource('maintenance-tasks', Admin\MaintenanceTaskController::class)->except('show')
        ->parameters(['maintenance-tasks' => 'maintenanceTask']);
});
