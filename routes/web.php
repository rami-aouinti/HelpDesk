<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceDetailController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::view('/api/documentation', 'api.documentation')->name('api.documentation');
Route::get('/api/openapi.yaml', function () {
    return response()->make(file_get_contents(base_path('openapi.yaml')), 200, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('api.openapi');

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/calendar/events', [CalendarController::class, 'feed'])->name('calendar.feed');
    Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::patch('/calendar/events/{event}', [CalendarController::class, 'update'])->name('calendar.events.update');
    Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
    Route::middleware('can:reports.view')->group(function (): void {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/csv', [ReportController::class, 'csv'])->name('reports.csv');
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::view('/appearance', 'appearance.index')->name('appearance.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::view('/help', 'help')->name('help');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'storeMessage'])->name('tickets.messages.store');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');

    Route::view('/teams', 'teams.index')->name('teams.index');
    Route::get('/teams/{team}', [WorkspaceDetailController::class, 'team'])->name('teams.show');
    Route::view('/sprints', 'sprints.index')->name('sprints.index');
    Route::get('/sprints/{sprint}', [WorkspaceDetailController::class, 'sprint'])->name('sprints.show');
    Route::view('/kanban', 'kanban.index')->name('kanban.index');
    Route::view('/projects', 'projects.index')->name('projects.index');
    Route::get('/projects/{project}', [WorkspaceDetailController::class, 'project'])->name('projects.show');
    Route::get('/projects/{project}/cover', [ProjectDocumentController::class, 'cover'])->name('projects.cover');
    Route::get('/project-documents/{document}/download', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');

    Route::resource('users', UserController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
    Route::patch('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
});
