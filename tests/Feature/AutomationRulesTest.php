<?php

namespace Tests\Feature;

use App\Automation\Application\AutomationRuleEngine;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_rule_combines_conditions_and_executes_multiple_actions(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::query()->create([
            'subject' => 'Cannot access account',
            'description' => 'Login fails.',
            'status' => 'open',
            'priority' => 'urgent',
            'user_id' => $customer->id,
        ]);

        $rule = AutomationRule::query()->create([
            'name' => 'Urgent login tickets',
            'trigger' => 'ticket.created',
            'conditions' => [
                'logic' => 'and',
                'rules' => [
                    ['field' => 'priority', 'operator' => 'equals', 'value' => 'urgent'],
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'open'],
                ],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'high'],
                ['type' => 'add_tag', 'value' => 'triage'],
            ],
            'is_active' => true,
        ]);

        app(AutomationRuleEngine::class)->dispatch('ticket.created', $ticket);

        $this->assertDatabaseHas('automation_executions', [
            'automation_rule_id' => $rule->id,
            'ticket_id' => $ticket->id,
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'priority' => 'high']);
        $this->assertDatabaseHas('ticket_tags', ['ticket_id' => $ticket->id, 'name' => 'triage']);
    }

    public function test_non_matching_rule_is_recorded_as_skipped(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::query()->create([
            'subject' => 'Normal request',
            'description' => 'Question',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $rule = AutomationRule::query()->create([
            'name' => 'Urgent only',
            'trigger' => 'ticket.created',
            'conditions' => ['logic' => 'and', 'rules' => [
                ['field' => 'priority', 'operator' => 'equals', 'value' => 'urgent'],
            ]],
            'actions' => [['type' => 'add_tag', 'value' => 'urgent']],
            'is_active' => true,
        ]);

        app(AutomationRuleEngine::class)->dispatch('ticket.created', $ticket);

        $this->assertDatabaseHas('automation_executions', [
            'automation_rule_id' => $rule->id,
            'status' => 'skipped',
        ]);
        $this->assertDatabaseMissing('ticket_tags', ['ticket_id' => $ticket->id, 'name' => 'urgent']);
    }

    public function test_admin_can_create_and_manage_automation_rules(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('automations.store'), [
            'name' => 'Tag new tickets',
            'trigger' => 'ticket.created',
            'conditions' => ['logic' => 'or', 'rules' => [
                ['field' => 'priority', 'operator' => 'equals', 'value' => 'high'],
                ['field' => 'status', 'operator' => 'equals', 'value' => 'open'],
            ]],
            'actions' => [['type' => 'add_tag', 'value' => 'new']],
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('automation_rules', ['name' => 'Tag new tickets', 'trigger' => 'ticket.created']);
    }
}
