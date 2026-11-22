<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Application\Commands\DispatchSlaAlerts;

use App\ServiceDesk\Application\Commands\DispatchSlaAlerts\DispatchSlaAlertsHandler;
use App\ServiceDesk\Application\Contracts\SlaAlertOutboxRepository;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;
use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Application\DTO\SlaAlertOutboxMessage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DispatchSlaAlertsHandlerTest extends TestCase
{
    public function test_it_publishes_and_marks_pending_alerts_as_dispatched(): void
    {
        $message = new SlaAlertOutboxMessage(7, new SlaAlert(42, 'resolution', 'warning', new DateTimeImmutable()));
        $outbox = $this->createMock(SlaAlertOutboxRepository::class);
        $outbox->expects(self::once())->method('pending')->with(25)->willReturn([$message]);
        $outbox->expects(self::once())->method('markDispatched')->with(7);
        $outbox->expects(self::never())->method('releaseForRetry');

        $publisher = $this->createMock(SlaAlertPublisher::class);
        $publisher->expects(self::once())->method('publish')->with($message->alert);

        self::assertSame(1, (new DispatchSlaAlertsHandler($outbox, $publisher))->handle(25));
    }

    public function test_it_keeps_failed_alerts_pending_for_retry(): void
    {
        $message = new SlaAlertOutboxMessage(8, new SlaAlert(42, 'first_response', 'breached', new DateTimeImmutable()));
        $outbox = $this->createMock(SlaAlertOutboxRepository::class);
        $outbox->expects(self::once())->method('pending')->with(100)->willReturn([$message]);
        $outbox->expects(self::never())->method('markDispatched');
        $outbox->expects(self::once())->method('releaseForRetry')->with(8, 'queue unavailable');

        $publisher = $this->createMock(SlaAlertPublisher::class);
        $publisher->expects(self::once())->method('publish')->willThrowException(new RuntimeException('queue unavailable'));

        self::assertSame(0, (new DispatchSlaAlertsHandler($outbox, $publisher))->handle());
    }
}
