<?php

use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\V1\TicketController as V1TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active', 'verified'])->group(function (): void {
    Route::post('/tokens', [TokenController::class, 'store'])->name('api.tokens.store');
    Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('api.tokens.destroy');
    Route::get('/user', fn (\Illuminate\Http\Request $request) => $request->user())->name('api.user');

    Route::prefix('v1')->name('api.v1.')->group(function (): void {
        Route::get('/tickets', [V1TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [V1TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [V1TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [V1TicketController::class, 'update'])->name('tickets.update');
    });
});
