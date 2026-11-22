<?php

use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\V1\TicketController as V1TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active', 'verified', 'throttle:api'])->group(function (): void {
    Route::post('/tokens', [TokenController::class, 'store'])->name('api.tokens.store');
    Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('api.tokens.destroy');
    Route::get('/user', fn (Request $request) => $request->user())->name('api.user');

    Route::prefix('v1')->name('api.v1.')->group(function (): void {
        Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');
        Route::get('/user', fn (Request $request) => $request->user())->name('user');
        Route::get('/tickets', [V1TicketController::class, 'index'])->middleware('cache.api.get')->name('tickets.index');
        Route::post('/tickets', [V1TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [V1TicketController::class, 'show'])->middleware('cache.api.get')->name('tickets.show');
        Route::patch('/tickets/{ticket}', [V1TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/messages', [V1TicketController::class, 'storeMessage'])->name('tickets.messages.store');
        Route::get('/tickets/{ticket}/attachments/{attachment}', [V1TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    });
});
