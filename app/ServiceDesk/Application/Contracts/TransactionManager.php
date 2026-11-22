<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TransactionManager
{
    /** @param callable(): mixed $operation */
    public function run(callable $operation): mixed;
}
