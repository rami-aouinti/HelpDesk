<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketMessageCreated;
use App\Events\TicketUpdated;
use App\Listeners\SendTicketCreatedNotifications;
use App\Listeners\SendTicketMessageNotifications;
use App\Listeners\SendTicketUpdatedNotifications;
use App\Listeners\IndexTicketInElasticsearch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Queue::before(function (\Illuminate\Queue\Events\JobProcessing $event): void {
            Log::info('queue.job.started', ['event' => 'queue.job.started', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts()]);
        });
        Queue::after(function (\Illuminate\Queue\Events\JobProcessed $event): void {
            Log::info('queue.job.completed', ['event' => 'queue.job.completed', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts()]);
        });
        Queue::exceptionOccurred(function (\Illuminate\Queue\Events\JobExceptionOccurred $event): void {
            Log::error('queue.job.failed', ['event' => 'queue.job.failed', 'job' => $event->job->resolveName(), 'queue' => $event->job->getQueue(), 'connection' => $event->connectionName, 'attempt' => $event->job->attempts(), 'exception' => $event->exception::class, 'message' => $event->exception->getMessage()]);
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
