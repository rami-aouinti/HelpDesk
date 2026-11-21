<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'role', 'status']);
        $this->resetPage();
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
        $users = User::query()
            ->with('roles')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = mb_strtolower(trim($this->search));

                $query->where(function ($query) use ($search): void {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($this->role !== '', fn ($query) => $query->role($this->role))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }
}
