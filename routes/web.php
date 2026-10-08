<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ReviewReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Front\ReviewController as FrontReviewController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | FRONT OFFICE
 |--------------------------------------------------------------------------
 */
Route::get('/', [App\Http\Controllers\Front\HomeController::class, 'index'])->name('front.home');

// Authentification (tâche commune)
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Avis & signalements (front office) — accessibles uniquement après une location terminée
Route::middleware('auth')->group(function () {
    Route::get('/reservations/{reservation}/avis', [FrontReviewController::class, 'create'])->name('front.reviews.create');
    Route::post('/reservations/{reservation}/avis', [FrontReviewController::class, 'store'])->name('front.reviews.store');
    Route::post('/avis/{review}/signaler', [FrontReviewController::class, 'report'])->name('front.reviews.report');
});

/*
 |--------------------------------------------------------------------------
 | BACK OFFICE (administrateur)
 |--------------------------------------------------------------------------
 */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Avis & signalements
    Route::resource('reviews', ReviewController::class)->except('show');
    Route::resource('review-reports', ReviewReportController::class)->except('show')
        ->parameters(['review-reports' => 'reviewReport']);
});