<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublicOrderController;
use Illuminate\Support\Facades\Route;

// Autenticação
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Pedidos públicos (acesso por token — sem auth)
Route::get('/public/orders/{token}',          [PublicOrderController::class, 'show']);
Route::patch('/public/orders/{token}/approve', [PublicOrderController::class, 'approve']);
Route::patch('/public/orders/{token}/reject',  [PublicOrderController::class, 'reject']);

// Rotas autenticadas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Rotas que exigem plano ativo
    Route::middleware('plan.active')->group(function () {
        Route::apiResource('clients', ClientController::class)->except(['show']);
        Route::apiResource('orders',  OrderController::class);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
});
