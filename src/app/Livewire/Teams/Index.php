<?php

namespace App\\Livewire\\Teams;

use App\\Models\\Team;
use App\\Models\\User;
use App\\Services\\TeamManagementService;
use Illuminate\\Contracts\\View\\View;
use Illuminate\\Support\\Facades\\Gate;
use Illuminate\\Validation\\Rule;
use Livewire\\Attributes\\Url;
use Livewire\\Component;
use Livewire\\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $teamId = null;
    public string $name = '';
    public string $description = '';
    public array $members = [];
    public bool $showForm = false;

    #[Url] public string $search = '';
    #[Url] public string $membership = '';
    #[Url] public string $sortBy = 'name';
    #[Url] public string $sortDirection = 'asc';
    public int $perPage = 10;

    public function mount(): void
    {
        Gate::authorize('viewAny', Team::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'membership', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        abort_unless(in_array($column, ['name', 'members_count', 'tickets_count', 'created_at'], true), 400);
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Team::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $teamId): void
    {
        $team = Team::query()->with('members')->findOrFail($teamId);
        Gate::authorize('update', $team);
        $this->teamId = $team->id;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
        $this->members = $team->members->modelKeys();
        $this->showForm = true;
    }

    public function save(TeamManagementService $teams): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($this->teamId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'members' => ['array'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        if ($this->teamId) {
            $team = Team::query()->findOrFail($this->teamId);
            Gate::authorize('update', $team);
            $teams->update($team, $this->only(['name', 'description', 'members']));
            session()->flash('success', 'Team updated successfully.');
        } else {
            Gate::authorize('create', Team::class);
            $teams->create($this->only(['name', 'description', 'members']));
            session()->flash('success', 'Team created successfully.');
        }
        $this->resetForm();
    }

    public function delete(int $teamId, TeamManagementService $teams): void
    {
        $team = Team::query()->findOrFail($teamId);
        Gate::authorize('delete', $team);
        $teams->delete($team);
        session()->flash('success', 'Team deleted successfully.');
    }

    public function cancel(): void { $this->resetForm(); }

    private function resetForm(): void
    {
        $this->reset(['teamId', 'name', 'description', 'members', 'showForm']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $teams = Team::query()
            ->with('members')
            ->withCount('tickets')
            ->withCount('members')
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('description', 'like', '%'.trim($this->search).'%')))
            ->when($this->membership === 'with_members', fn ($query) => $query->has('members'))
            ->when($this->membership === 'without_members', fn ($query) => $query->doesntHave('members'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.teams.index', [
            'teams' => $teams,
            'agents' => User::query()->role('agent')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }
}
