<?php

use App\Http\Controllers\PhotoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [UserController::class, 'signup']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/user/{user}/share', [PhotoController::class, 'share']);
    Route::get('/user', [UserController::class, 'search']);
    Route::apiResource('/photo', PhotoController::class);
});

