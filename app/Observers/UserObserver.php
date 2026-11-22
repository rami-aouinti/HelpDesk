<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\IndexUserInElasticsearch;
use App\Models\User;

class UserObserver
{
    public function saved(User $user): void
    {
        IndexUserInElasticsearch::dispatch((int) $user->getKey())->afterCommit();
    }

    public function deleted(User $user): void
    {
        IndexUserInElasticsearch::dispatch((int) $user->getKey())->afterCommit();
    }
}
