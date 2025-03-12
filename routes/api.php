<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TagController;



Route::post('register',[AuthController::class, 'register']);
Route::post('login',[AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('logout',[AuthController::class, 'logout']);
    Route::get('user',[AuthController::class, 'user']);

    Route::apiResource('expenses', ExpenseController::class);
    Route::post('/expenses/{id}/tags', [ExpenseController::class, 'attachTags']);

    Route::apiResource('tags', TagController::class);
});

