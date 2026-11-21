<?php

use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/tokens', [TokenController::class, 'store'])->name('api.tokens.store');
    Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('api.tokens.destroy');
    Route::get('/user', fn (\Illuminate\Http\Request $request) => $request->user())->name('api.user');
});
