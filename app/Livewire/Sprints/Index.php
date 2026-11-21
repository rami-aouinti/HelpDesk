<?php

namespace App\Livewire\Sprints;

use App\Models\Sprint;
use App\Services\SprintManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $sprintId = null;
    public string $name = '';
    public string $description = '';
    public string $startDate = '';
    public string $endDate = '';
    public bool $showForm = false;

    #[Url] public string $search = '';
    public int $perPage = 10;

    public function mount(): void
    {
        Gate::authorize('viewAny', Sprint::class);
    }

    #[On('sprints-search-updated')]
    public function updateSearch(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    #[On('sprints-create')]
    public function createFromTopbar(): void
    {
        $this->create();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        Gate::authorize('create', Sprint::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $sprintId): void
    {
        $sprint = Sprint::query()->findOrFail($sprintId);
        Gate::authorize('update', $sprint);

        $this->sprintId = $sprint->id;
        $this->name = $sprint->name;
        $this->description = $sprint->description ?? '';
        $this->startDate = $sprint->start_date->toDateString();
        $this->endDate = $sprint->end_date->toDateString();
        $this->showForm = true;
    }

    public function save(SprintManagementService $sprints): void
    {
        $this->name = trim($this->name);

        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sprints', 'name')->ignore($this->sprintId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $data = [
            'name' => trim($this->name),
            'description' => $this->description,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        if ($this->sprintId !== null) {
            $sprint = Sprint::query()->findOrFail($this->sprintId);
            Gate::authorize('update', $sprint);
            $sprints->update($sprint, $data);
            session()->flash('success', 'Sprint updated successfully.');
        } else {
            Gate::authorize('create', Sprint::class);
            $sprints->create($data, auth()->user());
            session()->flash('success', 'Sprint created successfully.');
        }

        $this->resetForm();
    }

    public function delete(int $sprintId, SprintManagementService $sprints): void
    {
        $sprint = Sprint::query()->findOrFail($sprintId);
        Gate::authorize('delete', $sprint);

        try {
            $sprints->delete($sprint);
            session()->flash('success', 'Sprint deleted successfully.');
        } catch (ValidationException $exception) {
            $this->addError('sprint', $exception->errors()['sprint'][0] ?? 'The sprint cannot be deleted.');
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['sprintId', 'name', 'description', 'startDate', 'endDate', 'showForm']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $perPage = min(max($this->perPage, 5), 50);

        $sprints = Sprint::query()
            ->with('creator:id,name')
            ->withCount('tickets')
            ->withCount(['tickets as done_tickets_count' => fn ($query) => $query->where('status', 'closed')])
            ->when(trim($this->search) !== '', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower(trim($this->search)).'%']))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return view('livewire.sprints.index', ['sprints' => $sprints]);
    }
}
