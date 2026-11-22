<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public int $perPage = 10;

    public ?int $selectedUserId = null;

    public ?int $editingUserId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $userRole = 'agent';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    #[On('users-search-updated')]
    public function updateSearch(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'role', 'status', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['name', 'email', 'created_at'], true), 400);

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'role', 'status']);
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', User::class);
        $this->resetUserForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $user = User::query()->with('roles')->findOrFail($userId);
        Gate::authorize('update', $user);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->userRole = $user->roles->first()?->name ?? 'agent';
        $this->showForm = true;
    }

    public function saveUser(UserManagementService $users): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'userRole' => ['required', Rule::in(['admin', 'agent', 'customer'])],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:12', 'same:passwordConfirmation'],
            'passwordConfirmation' => [$this->password !== '' ? 'required' : 'nullable', 'string'],
        ]);

        $data = ['name' => $this->name, 'email' => $this->email, 'role' => $this->userRole, 'password' => $this->password];
        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            Gate::authorize('update', $user);
            $users->update($user, $data);
            session()->flash('success', 'User updated successfully.');
        } else {
            Gate::authorize('create', User::class);
            $users->create($data);
            session()->flash('success', 'User created successfully. Verification email sent.');
        }

        $this->resetUserForm();
    }

    public function cancelUserForm(): void
    {
        $this->resetUserForm();
    }

    private function resetUserForm(): void
    {
        $this->reset(['editingUserId', 'showForm', 'name', 'email', 'userRole', 'password', 'passwordConfirmation']);
        $this->userRole = 'agent';
        $this->resetValidation();
    }

    public function showUser(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('view', $user);
        $this->selectedUserId = $user->id;
    }

    public function closeUser(): void
    {
        $this->selectedUserId = null;
    }

    public function toggleActive(int $userId, UserManagementService $users): void
    {
        $user = User::query()->findOrFail($userId);
        Gate::authorize('toggleActive', $user);
        $users->toggleActive($user);
        session()->flash('success', 'User status updated successfully.');
    }

    public function render(): View
    {
        $sortColumn = in_array($this->sortBy, ['name', 'email', 'created_at'], true) ? $this->sortBy : 'created_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $perPage = min(max($this->perPage, 5), 50);

        $users = User::query()
            ->with('roles')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = mb_strtolower(trim($this->search));
                $query->where(fn ($nested) => $nested->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]));
            })
            ->when($this->role !== '', fn ($query) => $query->role($this->role))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage);

        $selectedUser = $this->selectedUserId ? User::query()->with(['roles', 'teams', 'projects'])->find($this->selectedUserId) : null;

        return view('livewire.users.index', [
            'selectedUser' => $selectedUser,
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }
}
