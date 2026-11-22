<?php

namespace Tests\Feature;

use Tests\TestCase;

class HorizonConfigurationTest extends TestCase
{
    public function test_horizon_supervises_notifications_and_default_queues_using_the_dedicated_redis_connection(): void
    {
        $supervisor = config('horizon.defaults.supervisor-helpdesk');

        $this->assertSame('queue', config('horizon.use'));
        $this->assertSame('redis', $supervisor['connection']);
        $this->assertSame(['notifications', 'default'], $supervisor['queue']);
        $this->assertSame(5, $supervisor['tries']);
        $this->assertSame(90, $supervisor['timeout']);
        $this->assertSame(120, config('queue.connections.redis.retry_after'));
    }

    public function test_production_horizon_supervisor_has_a_bounded_worker_pool(): void
    {
        $supervisor = config('horizon.environments.production.supervisor-helpdesk');

        $this->assertSame(8, $supervisor['maxProcesses']);
        $this->assertSame(2, $supervisor['balanceMaxShift']);
        $this->assertSame(3, $supervisor['balanceCooldown']);
    }
}
