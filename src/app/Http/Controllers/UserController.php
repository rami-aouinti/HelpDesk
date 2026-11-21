<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('viewAny', User::class), 403);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = mb_strtolower(trim($request->string('search')->toString()));
                $query->where(function ($query) use ($search): void {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
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
