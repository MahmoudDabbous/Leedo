<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TagController;

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

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/verify', [AuthController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('/tags', TagController::class);

    Route::get('/posts/trash', [PostController::class, 'trash']);
    Route::get('/posts/{post}', [PostController::class, 'restore']);
    Route::post('/posts/{post}', [PostController::class, 'update']);
    Route::apiResource('/posts', PostController::class);
});

Route::get('/stats', StatsController::class);
