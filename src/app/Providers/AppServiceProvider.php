<?php

namespace App\\Providers;

use App\\Models\\User;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;
use Laravel\\Fortify\\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('email', Str::lower(trim((string) $request->input('email'))))
                ->first();

            if (! $user || ! $user->is_active || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });
    }
}
