<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamManagementService
{
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data): Team {
            $team = Team::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $team->members()->sync($this->validatedAgentIds($data['members'] ?? []));

            return $team->load('members');
        });
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data): Team {
            $team->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $team->members()->sync($this->validatedAgentIds($data['members'] ?? []));

            return $team->load('members');
        });
    }

    public function delete(Team $team): void
    {
        if ($team->tickets()->exists()) {
            throw ValidationException::withMessages([
                'team' => 'This team cannot be deleted while tickets are assigned to it.',
            ]);
        }

        $team->delete();
    }

    /**
     * @param  array<int, int|string>  $memberIds
     * @return array<int, int>
     */
    private function validatedAgentIds(array $memberIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $memberIds)));

        if ($ids === []) {
            return [];
        }

        $validIds = User::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->role('agent')
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($ids)) {
            throw ValidationException::withMessages([
                'members' => 'Only active users with the agent role can be added to a team.',
            ]);
        }

        return $validIds;
    }
}
