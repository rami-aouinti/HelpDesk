<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_start_conversation_and_agent_can_reply(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->assignRole('customer');
        $agent = User::factory()->create(['email_verified_at' => now()]);
        $agent->assignRole('agent');

        $start = $this->actingAs($customer)->postJson(route('live-chat.start'), [
            'subject' => 'Billing question',
        ])->assertCreated();

        $conversationId = $start->json('conversation_id');
        $conversation = ChatConversation::query()->findOrFail($conversationId);

        $this->actingAs($agent)->patchJson(route('live-chat.claim', $conversation))->assertOk();
        $this->actingAs($agent)->postJson(route('live-chat.send', $conversation), [
            'body' => 'Hello, I can help with your billing question.',
        ])->assertCreated();

        $this->actingAs($customer)->getJson(route('live-chat.messages', $conversation))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Hello, I can help with your billing question.');
    }

    public function test_customer_cannot_read_another_customers_conversation(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole('customer');
        $other = User::factory()->create(['email_verified_at' => now()]);
        $other->assignRole('customer');

        $conversation = ChatConversation::query()->create([
            'customer_id' => $owner->id,
            'status' => 'open',
        ]);

        $this->actingAs($other)->getJson(route('live-chat.messages', $conversation))->assertNotFound();
    }

    public function test_agent_can_convert_chat_transcript_to_ticket(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->assignRole('customer');
        $agent = User::factory()->create(['email_verified_at' => now()]);
        $agent->assignRole('agent');

        $conversation = ChatConversation::query()->create([
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'subject' => 'Payment issue',
            'status' => 'open',
        ]);
        $conversation->messages()->create(['user_id' => $customer->id, 'body' => 'My payment failed.']);

        $response = $this->actingAs($agent)->post(route('live-chat.convert', $conversation));

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'user_id' => $customer->id,
            'subject' => 'Payment issue',
        ]);
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $conversation->id,
            'status' => 'converted',
        ]);
    }
}
