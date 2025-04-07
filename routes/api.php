<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\GroupExpenseController;
use App\Http\Controllers\API\GroupBalanceController;



Route::post('register',[AuthController::class, 'register']);
Route::post('login',[AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('logout',[AuthController::class, 'logout']);
    Route::get('user',[AuthController::class, 'user']);

    Route::apiResource('expenses', ExpenseController::class);
    Route::post('/expenses/{id}/tags', [ExpenseController::class, 'attachTags']);

    Route::apiResource('tags', TagController::class);

    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/expenses', [GroupExpenseController::class, 'store']);
    Route::get('/groups/{group}/expenses', [GroupExpenseController::class, 'index']);
    Route::delete('/groups/{group}/expenses/{expense}', [GroupExpenseController::class, 'destroy']);

    Route::get('/groups/{group}/balances', [GroupBalanceController::class, 'index']);

});

