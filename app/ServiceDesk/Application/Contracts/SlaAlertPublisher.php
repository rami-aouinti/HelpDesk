<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Application\DTO\SlaAlert;

interface SlaAlertPublisher
{
    public function publish(SlaAlert $alert): void;
}
