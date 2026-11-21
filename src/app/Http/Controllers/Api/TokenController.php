<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $token = $request->user()->createToken($validated['name']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken || ! str_contains($plainTextToken, '|')) {
            return response()->json(['message' => 'A bearer token is required.'], 422);
        }

        [$tokenId, $plainTextSecret] = explode('|', $plainTextToken, 2);

        $deleted = $request->user()
            ->tokens()
            ->whereKey($tokenId)
            ->where('token', hash('sha256', $plainTextSecret))
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
