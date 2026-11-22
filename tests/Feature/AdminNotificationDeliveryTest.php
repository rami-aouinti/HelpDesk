<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Communications\Application\Contracts\NotificationDeliveryRepository;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_delivery_history_and_filter_failed_deliveries(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $repository = app(NotificationDeliveryRepository::class);
        $now = new DateTimeImmutable('2026-11-22T12:00:00+00:00');

        $repository->recordAttempt('delivery-sent', $agent->getMorphClass(), (string) $agent->id, 'App\\Notifications\\TicketActivityNotification', 'mail', $now);
        $repository->recordSent('delivery-sent', $agent->getMorphClass(), (string) $agent->id, 'mail', $now);

        $repository->recordAttempt('delivery-failed', $agent->getMorphClass(), (string) $agent->id, 'App\\Notifications\\TicketSlaAlertNotification', 'mail', $now);
        $repository->recordFailed('delivery-failed', $agent->getMorphClass(), (string) $agent->id, 'mail', 'SMTP connection refused', $now);

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index'))
            ->assertOk()
            ->assertSee('Notification deliveries')
            ->assertSee('delivery-sent')
            ->assertSee('delivery-failed')
            ->assertSee('SMTP connection refused')
            ->assertSee('Support Agent');

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('delivery-failed')
            ->assertDontSee('delivery-sent');
    }

    public function test_non_admin_cannot_view_delivery_history(): void
    {
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($agent)
            ->get(route('admin.communications.deliveries.index'))
            ->assertForbidden();
    }

    public function test_delivery_filter_validation_rejects_unknown_status(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.communications.deliveries.index', ['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }
}
