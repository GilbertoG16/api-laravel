<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EntryController;

use App\Http\Controllers\Learning\LocationController;

use Illuminate\Foundation\Auth\EmailVerificationRequest;


use App\Http\Controllers\VerifyEmailController;

// ...

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Ruta para registrarse como usuario
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum', 'verified', 'status')->group(function () {
    Route::get('/profile', [AuthController::class, 'userProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::put('/user/profile/updated', [UserController::class, 'updateProfile']);
    Route::post('/user/upload/profile', [UserController::class, 'uploadProfilePhoto']);
});

Route::middleware('auth:sanctum','verified', 'role:superadmin', 'status' )->group(function () {
    Route::post('/user/register', [SuperAdminController::class, 'register']);
    Route::get('/user', [SuperAdminController::class, 'index']);
    Route::get('/user/{id}', [SuperAdminController::class, 'show']);
    Route::put('/user/{id}', [SuperAdminController::class, 'update']);
    Route::delete('/user/{id}', [SuperAdminController::class, 'destroy']);

    Route::get('/roles', [SuperAdminController::class, 'getAllRoles']);

    Route::put('/user/{id}/ban', [SuperAdminController::class, 'ban']);
    Route::post('/entries', [EntryController::class, 'store']);
});

Route::middleware('auth:sanctum','verified', 'role:admin', 'status' )->group(function () {
    Route::post('/admin/locations', [LocationController::class, 'store']);
    Route::put('/admin/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/admin/locations/{id}', [LocationController::class, 'destroy']);
});


/*-- Verificación de EMAIL y cambio de contraseña */
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::get('/email/resend', [VerifyEmailController::class, 'resendEmail'])
    ->middleware(['auth:sanctum'])->name('verification.resend');

Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/reset-password/{id}/{token}', [AuthController::class, 'resetPassword']);



 











