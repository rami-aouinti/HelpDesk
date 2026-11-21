<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function create(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $role = Role::query()->where('name', $data['role'])->where('guard_name', 'web')->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->syncRoles([$role]);

            return $user;
        });

        event(new Registered($user));

        return $user;
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if ($user->hasRole('admin') && $data['role'] !== 'admin') {
                $this->ensureAnotherActiveAdministratorExists($user);
            }

            $role = Role::query()->where('name', $data['role'])->where('guard_name', 'web')->firstOrFail();

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                ...(! empty($data['password']) ? ['password' => $data['password']] : []),
            ]);

            $user->syncRoles([$role]);

            return $user->refresh();
        });
    }

    public function toggleActive(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            if ($user->is_active && $user->hasRole('admin')) {
                $this->ensureAnotherActiveAdministratorExists($user);
            }

            $user->forceFill(['is_active' => ! $user->is_active])->save();

            return $user->refresh();
        });
    }

    private function ensureAnotherActiveAdministratorExists(User $user): void
    {
        $otherActiveAdministrators = User::query()
            ->where('id', '!=', $user->getKey())
            ->where('is_active', true)
            ->role('admin')
            ->exists();

        if (! $otherActiveAdministrators) {
            throw ValidationException::withMessages([
                'role' => 'At least one active administrator must remain in the system.',
            ]);
        }
    }
}
