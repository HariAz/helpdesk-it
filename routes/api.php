<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TokenController;

// Public: issue token
Route::post('/v1/auth/token', [TokenController::class, 'issue']);

// Authenticated API routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    Route::delete('/auth/token',  [TokenController::class, 'revoke']);
    Route::get('/auth/tokens',    [TokenController::class, 'list']);

    // Tickets
    Route::get('/tickets',                          [TicketController::class, 'index']);
    Route::post('/tickets',                         [TicketController::class, 'store']);
    Route::get('/tickets/{ticketNumber}',           [TicketController::class, 'show']);
    Route::patch('/tickets/{ticketNumber}/status',  [TicketController::class, 'updateStatus']);
    Route::patch('/tickets/{ticketNumber}/assign',  [TicketController::class, 'assign']);

    // Stats (supervisor only, enforced inside controller)
    Route::get('/stats', [StatsController::class, 'index']);
});
