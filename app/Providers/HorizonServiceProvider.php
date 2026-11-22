<?php

namespace App\\Providers;

use App\\Models\\User;
use Illuminate\\Support\\Facades\\Gate;
use Laravel\\Horizon\\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Register the Horizon authorization gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static fn (User $user): bool => $user->hasRole('administrator'));
    }
}
