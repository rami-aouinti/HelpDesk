<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_users_with_permission_can_search_users_by_name_or_email(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users/search?q=customer')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Demo Customer',
                'email' => 'customer@helpdesk.test',
            ]);
    }

    public function test_users_without_user_view_permission_cannot_search_users(): void
    {
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/users/search?q=admin')
            ->assertForbidden();
    }

    public function test_user_search_requires_a_valid_query(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users/search?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
