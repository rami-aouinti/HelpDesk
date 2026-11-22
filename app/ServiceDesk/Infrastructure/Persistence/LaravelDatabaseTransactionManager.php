<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence;

use App\ServiceDesk\Application\Contracts\TransactionManager;
use Illuminate\Support\Facades\DB;

final class LaravelDatabaseTransactionManager implements TransactionManager
{
    public function run(callable $operation): mixed
    {
        return DB::transaction($operation);
    }
}
