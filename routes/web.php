<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HelpAssistantController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ReviewReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Front\EquipmentController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\ReviewController as FrontReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Owner\ReviewReplyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FRONT OFFICE
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('front.home');

Route::get('/equipements', [EquipmentController::class, 'index'])->name('front.equipements.index');
Route::get('/equipements/{equipment}', [EquipmentController::class, 'show'])->name('front.equipements.show');

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
    Route::get('/avis/{review}/modifier', [FrontReviewController::class, 'edit'])->name('front.reviews.edit')->middleware('throttle:10,1');
    Route::put('/avis/{review}/modifier', [FrontReviewController::class, 'update'])->name('front.reviews.update')->middleware('throttle:10,1');
});
Route::middleware(['auth', 'throttle:5,1'])->group(function () {
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
    Route::delete('reviews/{review}/photos/{photo}', [ReviewController::class, 'destroyPhoto'])->name('reviews.photos.destroy');
    Route::resource('review-reports', ReviewReportController::class)
        ->parameters(['review-reports' => 'reviewReport']);
    Route::post('review-reports/{reviewReport}/traiter', [ReviewReportController::class, 'traiter'])->name('review-reports.traiter');
    Route::post('review-reports/{reviewReport}/rejeter', [ReviewReportController::class, 'rejeter'])->name('review-reports.rejeter');
    Route::get('moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::get('moderation/export', [ModerationController::class, 'export'])->name('moderation.export');
    Route::get('moderation/pdf', [ModerationController::class, 'pdf'])->name('moderation.pdf');
    Route::post('help-assistant', [HelpAssistantController::class, 'ask'])->name('help.ask')->middleware('throttle:20,1');
});
// Proprietaire
Route::middleware(['auth'])->prefix('proprietaire')->name('owner.')->group(function () {
    Route::get('/avis', [ReviewReplyController::class, 'index'])->name('reviews.index');
    Route::post('/avis/{review}/reponse', [ReviewReplyController::class, 'store'])->middleware('throttle:10,1')->name('reviews.reply');
    Route::post('/avis/{review}/reponse/suggestion', [ReviewReplyController::class, 'suggest'])->name('reviews.suggest');
});
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::post('/avis/{review}/utile', [FrontReviewController::class, 'vote'])->name('front.reviews.vote');
});
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
});
