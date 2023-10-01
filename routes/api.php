<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\SuperAdminController;
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

Route::middleware('auth:sanctum', 'status')->group(function () {
    Route::get('/profile', [AuthController::class, 'userProfile']);
    Route::get('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum', 'role:superadmin', 'status' )->group(function () {
    Route::post('/user/register', [SuperAdminController::class, 'register']);
    Route::get('/user', [SuperAdminController::class, 'index']);
    Route::get('/user/{id}', [SuperAdminController::class, 'show']);
    Route::put('/user/{id}', [SuperAdminController::class, 'update']);
    Route::delete('/user/{id}', [SuperAdminController::class, 'destroy']);

    Route::get('/roles', [SuperAdminController::class, 'getAllRoles']);

    Route::put('/user/{id}/ban', [SuperAdminController::class, 'ban']);
 
});

