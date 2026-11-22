<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserSearchController extends Controller
{
    public function __invoke(Request $request, UserSearchService $search): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $users = $search->search(trim($validated['q']));

        return response()->json([
            'data' => $users->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->values(),
            ]),
        ]);
    }
}
