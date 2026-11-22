<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Exceptions;

use DomainException;

final class InvalidTicketTransition extends DomainException
{
}
