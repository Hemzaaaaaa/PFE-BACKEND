<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;

use App\Http\Controllers\CarController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

// Email verification
Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum');


/*
|--------------------------------------------------------------------------
| CARS
|--------------------------------------------------------------------------
*/

// Public
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{id}', [CarController::class, 'show']);
Route::post('/cars/{id}/upload-image', [CarController::class, 'uploadImage']);

// Admin/Staff
Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(function () {
    Route::post('/cars', [CarController::class, 'store']);
    Route::put('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);

    // Multiple image upload
    Route::post('/cars/{id}/upload-images', [CarController::class, 'uploadImages']);
});


/*
|--------------------------------------------------------------------------
| RESERVATIONS
|--------------------------------------------------------------------------
*/

// Calendar endpoint (public)
Route::get('/reservations/car/{id}', [ReservationController::class, 'calendar']);

// User (must be verified)
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
});

// Admin/Staff
Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::put('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
});


/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(function () {
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/dashboard/monthly-stats', [AdminDashboardController::class, 'monthlyStats']);
});
