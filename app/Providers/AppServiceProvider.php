<?php

namespace App\Providers;

use App\Communications\Application\Contracts\NotificationInboxRepository;
use App\Communications\Infrastructure\Persistence\Eloquent\EloquentNotificationInboxRepository;

use App\Insights\Application\Contracts\DashboardReadRepository;
use App\Insights\Application\Contracts\ReportReadRepository;
use App\Insights\Application\Contracts\ReportCsvExportRepository;
use App\Insights\Infrastructure\Persistence\Eloquent\EloquentDashboardReadRepository;
use App\Insights\Infrastructure\Persistence\Eloquent\EloquentReportReadRepository;
use App\Insights\Infrastructure\Persistence\Eloquent\EloquentReportCsvExportRepository;

use App\Events\TicketCreated;
use App\ServiceDesk\Application\Contracts\TicketCreatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketMessageRepository;
use App\ServiceDesk\Application\Contracts\TicketAttachmentStorage;
use App\ServiceDesk\Application\Contracts\TicketMessageCreatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;
use App\ServiceDesk\Application\Contracts\TicketClaimedPublisher;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;
use App\ServiceDesk\Application\Contracts\TicketProjectAccess;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;
use App\ServiceDesk\Application\Contracts\SlaAlertOutboxRepository;
use App\ServiceDesk\Application\Contracts\SlaPolicyProvider;
use App\ServiceDesk\Application\Contracts\TicketSlaRepository;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketWorkflowPermission;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Repositories\TicketRepository;
use App\ServiceDesk\Infrastructure\Events\LaravelTicketCreatedPublisher;
use App\ServiceDesk\Infrastructure\Events\LaravelTicketMessageCreatedPublisher;
use App\ServiceDesk\Infrastructure\Events\LaravelTicketClaimedPublisher;
use App\ServiceDesk\Infrastructure\Events\LaravelTicketUpdatedPublisher;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketRepository;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketMessageRepository;
use App\ServiceDesk\Infrastructure\Storage\LaravelTicketAttachmentStorage;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketAssignmentPermission;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketTeamMembership;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketProjectAccess;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketHistoryRecorder;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketWorkflowPermission;
use App\ServiceDesk\Infrastructure\Persistence\LaravelDatabaseTransactionManager;
use App\ServiceDesk\Infrastructure\Sla\LaravelSlaAlertPublisher;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentSlaAlertOutboxRepository;
use App\ServiceDesk\Infrastructure\Sla\ConfigSlaPolicyProvider;
use App\ServiceDesk\Infrastructure\Persistence\Eloquent\EloquentTicketSlaRepository;
use App\Events\TicketMessageCreated;
use App\Events\TicketUpdated;
use App\Listeners\SendTicketCreatedNotifications;
use App\Listeners\SendTicketMessageNotifications;
use App\Listeners\SendTicketUpdatedNotifications;
use App\Listeners\IndexTicketInElasticsearch;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationInboxRepository::class, EloquentNotificationInboxRepository::class);
        $this->app->bind(DashboardReadRepository::class, EloquentDashboardReadRepository::class);
        $this->app->bind(ReportReadRepository::class, EloquentReportReadRepository::class);
        $this->app->bind(ReportCsvExportRepository::class, EloquentReportCsvExportRepository::class);
        $this->app->bind(TicketRepository::class, EloquentTicketRepository::class);
        $this->app->bind(TicketMessageRepository::class, EloquentTicketMessageRepository::class);
        $this->app->bind(TicketAttachmentStorage::class, LaravelTicketAttachmentStorage::class);
        $this->app->bind(TicketMessageCreatedPublisher::class, LaravelTicketMessageCreatedPublisher::class);
        $this->app->bind(TicketAssignmentPermission::class, EloquentTicketAssignmentPermission::class);
        $this->app->bind(TicketCreatedPublisher::class, LaravelTicketCreatedPublisher::class);
        $this->app->bind(TicketClaimedPublisher::class, LaravelTicketClaimedPublisher::class);
        $this->app->bind(TicketTeamMembership::class, EloquentTicketTeamMembership::class);
        $this->app->bind(TicketProjectAccess::class, EloquentTicketProjectAccess::class);
        $this->app->bind(TicketHistoryRecorder::class, EloquentTicketHistoryRecorder::class);
        $this->app->bind(SlaAlertPublisher::class, LaravelSlaAlertPublisher::class);
        $this->app->bind(SlaAlertOutboxRepository::class, EloquentSlaAlertOutboxRepository::class);
        $this->app->bind(SlaPolicyProvider::class, ConfigSlaPolicyProvider::class);
        $this->app->bind(TicketSlaRepository::class, EloquentTicketSlaRepository::class);
        $this->app->bind(TicketUpdatedPublisher::class, LaravelTicketUpdatedPublisher::class);
        $this->app->bind(TicketWorkflowPermission::class, EloquentTicketWorkflowPermission::class);
        $this->app->bind(TransactionManager::class, LaravelDatabaseTransactionManager::class);
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(120)->by(
                (string) ($request->user()?->getAuthIdentifier() ?? $request->ip()),
            );
        });

        Queue::before(function (\Illuminate\Queue\Events\JobProcessing $event): void {
            Log::info('queue.job.started', ['event' => 'queue.job.started', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts()]);
        });
        Queue::after(function (\Illuminate\Queue\Events\JobProcessed $event): void {
            Log::info('queue.job.completed', ['event' => 'queue.job.completed', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts()]);
        });
        Queue::exceptionOccurred(function (\Illuminate\Queue\Events\JobExceptionOccurred $event): void {
            Log::error('queue.job.failed', ['event' => 'queue.job.failed', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts(), 'exception' => $event->exception::class]);
        });
        Event::listen(QueueBusy::class, function (QueueBusy $event): void {
            Log::warning('queue.backlog.threshold_exceeded', [
                'event' => 'queue.backlog.threshold_exceeded',
                'connection' => $event->connectionName,
                'queue' => $event->queue,
                'size' => $event->size,
            ]);
        });
        Event::listen(TicketCreated::class, SendTicketCreatedNotifications::class);
        Event::listen(TicketMessageCreated::class, SendTicketMessageNotifications::class);
        Event::listen(TicketUpdated::class, SendTicketUpdatedNotifications::class);
        Event::listen(TicketCreated::class, IndexTicketInElasticsearch::class);
        Event::listen(TicketUpdated::class, IndexTicketInElasticsearch::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('email', Str::lower(trim((string) $request->input('email'))))
                ->first();

            if (! $user || ! $user->is_active || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });
    }
}
