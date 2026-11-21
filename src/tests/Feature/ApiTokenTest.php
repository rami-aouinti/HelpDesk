<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_use_a_sanctum_token(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $response = $this->actingAs($user)->postJson('/api/tokens', ['name' => 'Integration test']);
        $response->assertCreated()->assertJsonStructure(['token', 'token_type']);

        $token = $response->json('token');
        Auth::guard('web')->logout();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->withToken($token)
            ->deleteJson('/api/tokens/current')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();

        $tokenId = explode('|', $token, 2)[0];
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_api_token_creation_requires_authentication(): void
    {
        $this->postJson('/api/tokens', ['name' => 'Unauthenticated'])
            ->assertUnauthorized();
    }
}
