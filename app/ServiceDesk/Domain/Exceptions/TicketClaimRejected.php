<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Exceptions;

use RuntimeException;

final class TicketClaimRejected extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
