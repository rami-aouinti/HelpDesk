<?php

use App\Http\Controllers\KnowledgeBaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'publicIndex'])->name('knowledge-base.index');
    Route::get('/knowledge-base/suggestions', [KnowledgeBaseController::class, 'suggestions'])->name('knowledge-base.suggestions');
    Route::get('/knowledge-base/attachments/{attachment}', [KnowledgeBaseController::class, 'download'])->name('knowledge-base.attachments.download');
    Route::get('/knowledge-base/{article:slug}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');

    Route::middleware('can:knowledge-base.manage')->prefix('admin/knowledge-base')->name('knowledge-base.admin.')->group(function (): void {
        Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
        Route::get('/create', [KnowledgeBaseController::class, 'create'])->name('create');
        Route::post('/', [KnowledgeBaseController::class, 'store'])->name('store');
        Route::get('/{article}/edit', [KnowledgeBaseController::class, 'edit'])->name('edit');
        Route::put('/{article}', [KnowledgeBaseController::class, 'update'])->name('update');
        Route::delete('/{article}', [KnowledgeBaseController::class, 'destroy'])->name('destroy');
    });
});
