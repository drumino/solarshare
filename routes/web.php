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
});
