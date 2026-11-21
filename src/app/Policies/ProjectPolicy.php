<?php
namespace App\Policies;
use App\Models\Project;
use App\Models\User;
class ProjectPolicy {
 public function viewAny(User $user): bool { return $user->can('projects.view'); }
 public function view(User $user, Project $project): bool { return $user->can('projects.view.all') || ($user->can('projects.view') && $project->members()->whereKey($user->id)->exists()); }
 public function create(User $user): bool { return $user->can('projects.create'); }
 public function update(User $user, Project $project): bool { return $user->can('projects.update'); }
 public function delete(User $user, Project $project): bool { return $user->can('projects.delete'); }
 public function manageDocuments(User $user, Project $project): bool { return $this->view($user,$project) && ($user->can('projects.update') || $project->members()->whereKey($user->id)->exists()); }
}