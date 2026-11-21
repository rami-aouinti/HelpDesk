<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('viewAny', User::class), 403);

        return view('users.index');
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', User::class), 403);

        return view('users.create', [
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request, UserManagementService $users): RedirectResponse
    {
        $user = $users->create($request->validated());

        return redirect()->route('users.index')->with('success', "User {$user->name} created successfully.");
    }

    public function edit(User $user): View
    {
        abort_unless(auth()->user()->can('update', $user), 403);

        return view('users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserManagementService $users): RedirectResponse
    {
        $users->update($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user, UserManagementService $users): RedirectResponse
    {
        abort_unless(auth()->user()->can('toggleActive', $user), 403);
        $users->toggleActive($user);

        return redirect()->route('users.index')->with('success', 'User status updated successfully.');
    }
}
