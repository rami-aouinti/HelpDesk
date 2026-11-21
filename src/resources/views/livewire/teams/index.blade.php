<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="page-header">
        <div>
            <h1>Teams</h1>
            <p>Organize active support agents into teams.</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="create">+ Create team</button>
    </div>

    @if ($showForm)
        <section class="content-card team-form-card">
            <h2>{{ $teamId ? 'Edit team' : 'Create team' }}</h2>
            <form wire:submit="save">
                <div class="form-group">
                    <label for="team-name">Team name</label>
                    <input id="team-name" type="text" wire:model="name" maxlength="255" required>
                    @error('name') <small class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="team-description">Description</label>
                    <textarea id="team-description" wire:model="description" rows="3" maxlength="2000"></textarea>
                    @error('description') <small class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Team members (active agents)</label>
                    @forelse ($agents as $agent)
                        <label class="team-member-option">
                            <input type="checkbox" wire:model="members" value="{{ $agent->id }}">
                            <span>{{ $agent->name }} <small>{{ $agent->email }}</small></span>
                        </label>
                    @empty
                        <p>No active agents available. Create an agent account first.</p>
                    @endforelse
                    @error('members') <small class="field-error">{{ $message }}</small> @enderror
                    @error('members.*') <small class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save team</button>
                    <button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button>
                </div>
            </form>
        </section>
    @endif

    <section class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Members</th>
                        <th>Tickets</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teams as $team)
                        <tr wire:key="team-{{ $team->id }}">
                            <td>
                                <strong>{{ $team->name }}</strong>
                                <small>{{ $team->description ?: 'No description' }}</small>
                            </td>
                            <td>
                                {{ $team->members_count }}
                                <small>{{ $team->members->pluck('name')->join(', ') ?: 'No members assigned' }}</small>
                            </td>
                            <td>{{ $team->tickets_count }}</td>
                            <td class="actions">
                                <button type="button" class="btn btn-secondary" wire:click="edit({{ $team->id }})">Edit</button>
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    wire:click="delete({{ $team->id }})"
                                    wire:confirm="Delete the team {{ $team->name }}?"
                                >Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">No teams have been created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
