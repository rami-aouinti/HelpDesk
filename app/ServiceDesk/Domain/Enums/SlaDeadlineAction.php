<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Enums;

enum SlaDeadlineAction: string
{
    case NONE = 'none';
    case WARNING = 'warning';
    case BREACHED = 'breached';
}
