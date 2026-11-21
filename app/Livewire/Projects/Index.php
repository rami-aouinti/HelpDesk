<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $projectId = null;

    public string $name = '';

    public string $description = '';

    public string $budget = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $status = 'planned';

    public array $members = [];

    public $coverImage = null;

    public $document = null;

    public bool $showForm = false;

    public bool $showDetails = false;

    #[Url]
    public string $search = '';

    public int $perPage = 12;

    public function mount(): void
    {
        Gate::authorize('viewAny', Project::class);
    }

    #[On('projects-search-updated')]
    public function updateSearch(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    #[On('projects-create')]
    public function createFromTopbar(): void
    {
        $this->create();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Project::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $p = Project::with('members')->findOrFail($id);
        Gate::authorize('update', $p);
        $this->projectId = $p->id;
        $this->name = $p->name;
        $this->description = $p->description ?? '';
        $this->budget = (string) ($p->budget ?? '');
        $this->startDate = $p->start_date?->format('Y-m-d') ?? '';
        $this->endDate = $p->end_date?->format('Y-m-d') ?? '';
        $this->status = $p->status;
        $this->members = $p->members->modelKeys();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate(['name' => ['required', 'string', 'min:2', 'max:255', Rule::unique('projects', 'name')->ignore($this->projectId)], 'description' => ['nullable', 'string', 'max:10000'], 'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'], 'startDate' => ['nullable', 'date'], 'endDate' => ['nullable', 'date', 'after_or_equal:startDate'], 'status' => ['required', Rule::in(Project::STATUSES)], 'members' => ['array', 'max:100'], 'members.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true))], 'coverImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $p = $this->projectId ? Project::findOrFail($this->projectId) : new Project();
        Gate::authorize($this->projectId ? 'update' : 'create', $p);
        foreach ($this->members as $id) {
            $u = User::findOrFail($id);
            abort_unless($u->hasAnyRole(['agent', 'customer']), 422);
        }DB::transaction(function () use ($p): void {
            $p->name = $this->name;
            $p->description = $this->description ?: null;
            $p->budget = $this->budget !== '' ? $this->budget : null;
            $p->start_date = $this->startDate ?: null;
            $p->end_date = $this->endDate ?: null;
            $p->status = $this->status;
            if (! $p->exists) {
                $p->created_by = auth()->id();
            }$p->save();
            $p->members()->sync($this->members);
            if ($this->coverImage) {
                $old = $p->cover_image_path;
                $path = $this->coverImage->store('projects/covers', 'local');
                $p->update(['cover_image_path' => $path]);
                if ($old) {
                    Storage::disk('local')->delete($old);
                }
            }
        });
        session()->flash('success', 'Project saved successfully.');
        $this->resetForm();
    }

    public function show(int $id): void
    {
        $p = Project::with('members')->findOrFail($id);
        Gate::authorize('view', $p);
        $this->projectId = $p->id;
        $this->showDetails = true;
        $this->showForm = false;
    }

    public function uploadDocument(): void
    {
        $p = Project::findOrFail($this->projectId);
        Gate::authorize('manageDocuments', $p);
        $this->validate(['document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg', 'max:20480']]);
        $path = $this->document->store("projects/{$p->id}/documents", 'local');
        $p->documents()->create(['uploaded_by' => auth()->id(), 'disk' => 'local', 'path' => $path, 'original_name' => mb_strcut(basename($this->document->getClientOriginalName()), 0, 255, 'UTF-8'), 'mime_type' => $this->document->getMimeType(), 'size' => $this->document->getSize()]);
        $this->reset('document');
        session()->flash('success', 'Document uploaded.');
    }

    public function deleteDocument(int $id): void
    {
        $d = ProjectDocument::findOrFail($id);
        Gate::authorize('update', $d->project);
        Storage::disk($d->disk)->delete($d->path);
        $d->delete();
    }

    public function delete(int $id): void
    {
        $p = Project::findOrFail($id);
        Gate::authorize('delete', $p);
        DB::transaction(function () use ($p): void {
            $p->tickets()->update(['project_id' => null]);
            foreach ($p->documents as $d) {
                Storage::disk($d->disk)->delete($d->path);
            }if ($p->cover_image_path) {
                Storage::disk('local')->delete($p->cover_image_path);
            }$p->delete();
        });
        session()->flash('success', 'Project deleted; related tickets were kept.');
        $this->reset(['projectId', 'showDetails']);
    }

    private function resetForm(): void
    {
        $this->reset(['projectId', 'name', 'description', 'budget', 'startDate', 'endDate', 'status', 'members', 'coverImage', 'document', 'showForm', 'showDetails']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $u = auth()->user();
        $projects = Project::withCount(['members', 'tickets', 'documents'])->with('members:id,name,email')->when(! $u->can('projects.view.all'), fn ($q) => $q->whereHas('members', fn ($m) => $m->whereKey($u->id)))->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($n) => $n->where('name', 'like', '%' . trim($this->search) . '%')->orWhere('description', 'like', '%' . trim($this->search) . '%')))->latest()->paginate(min(max($this->perPage, 6), 30));
        $users = User::where('is_active', true)->whereHas('roles', fn ($q) => $q->whereIn('name', ['agent', 'customer']))->orderBy('name')->get(['id', 'name', 'email']);
        $selectedProject = $this->showDetails && $this->projectId ? Project::with(['members', 'documents.uploader'])->find($this->projectId) : null;
        if ($selectedProject) {
            Gate::authorize('view', $selectedProject);
            $ticketQuery = $selectedProject->tickets();
            if ($u->hasRole('customer')) {
                $ticketQuery->where('user_id', $u->id);
            } elseif ($u->hasRole('agent') && ! $u->can('tickets.view.all')) {
                $teamIds = $u->teams()->pluck('teams.id');
                $ticketQuery->where(function ($q) use ($teamIds) {
                    $q->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                });
            }$selectedProject->setRelation('tickets', $ticketQuery->latest()->get());
        }

        return view('livewire.projects.index', compact('projects', 'users', 'selectedProject'));
    }
}
