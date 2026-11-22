<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        if (config('app.env') === 'testing' || app()->runningUnitTests()) {
            return;
        }

        $teams = Team::query()->get()->keyBy('name');
        $customers = User::role('customer')->where('is_active', true)->orderBy('id')->get();
        $agents = User::role('agent')->where('is_active', true)->orderBy('id')->get();

        $definitions = [
            ['subject' => 'Unable to reset my password', 'description' => 'The password reset link expires immediately after I open it.', 'customer' => 'sophie.customer@helpdesk.test', 'team' => 'Account Management', 'agent' => 'amira.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'high'],
            ['subject' => 'Invoice for October is incorrect', 'description' => 'The invoice contains an additional charge that I do not recognize.', 'customer' => 'thomas.customer@helpdesk.test', 'team' => 'Billing Support', 'agent' => 'emma.agent@helpdesk.test', 'status' => 'waiting_for_customer', 'priority' => 'urgent'],
            ['subject' => 'Application crashes on startup', 'description' => 'The desktop application closes a few seconds after launch.', 'customer' => 'nour.customer@helpdesk.test', 'team' => 'Technical Support', 'agent' => 'lucas.agent@helpdesk.test', 'status' => 'open', 'priority' => 'urgent'],
            ['subject' => 'How can I update my profile?', 'description' => 'I would like to change the phone number associated with my account.', 'customer' => 'maxime.customer@helpdesk.test', 'team' => 'General Support', 'agent' => 'agent@helpdesk.test', 'status' => 'resolved', 'priority' => 'low'],
            ['subject' => 'Payment was charged twice', 'description' => 'My bank statement shows two charges for the same subscription.', 'customer' => 'lina.customer@helpdesk.test', 'team' => 'Billing Support', 'agent' => 'sarah.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'high'],
            ['subject' => 'VPN connection keeps dropping', 'description' => 'The VPN disconnects every ten to fifteen minutes while working remotely.', 'customer' => 'adam.customer@helpdesk.test', 'team' => 'Technical Support', 'agent' => 'youssef.agent@helpdesk.test', 'status' => 'open', 'priority' => 'normal'],
            ['subject' => 'Request to close my account', 'description' => 'Please explain the steps required to permanently close my account.', 'customer' => 'clara.customer@helpdesk.test', 'team' => 'Account Management', 'agent' => 'daniel.agent@helpdesk.test', 'status' => 'closed', 'priority' => 'normal'],
            ['subject' => 'Cannot access shared documents', 'description' => 'Shared documents return an access denied message.', 'customer' => 'mehdi.customer@helpdesk.test', 'team' => 'General Support', 'agent' => 'agent@helpdesk.test', 'status' => 'waiting_for_customer', 'priority' => 'high'],
            ['subject' => 'Feature request: export reports', 'description' => 'It would be useful to export monthly reports as CSV files.', 'customer' => 'sophie.customer@helpdesk.test', 'team' => 'Product & Integrations', 'agent' => 'sarah.agent@helpdesk.test', 'status' => 'resolved', 'priority' => 'low'],
            ['subject' => 'Two-factor authentication issue', 'description' => 'I changed my phone and can no longer receive authentication codes.', 'customer' => 'thomas.customer@helpdesk.test', 'team' => 'Technical Support', 'agent' => 'amira.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'urgent'],
            ['subject' => 'Subscription renewal question', 'description' => 'I need clarification about the date and amount of my next renewal.', 'customer' => 'nour.customer@helpdesk.test', 'team' => 'Billing Support', 'agent' => 'emma.agent@helpdesk.test', 'status' => 'open', 'priority' => 'normal'],
            ['subject' => 'Welcome email was not received', 'description' => 'I created an account yesterday but did not receive the welcome email.', 'customer' => 'maxime.customer@helpdesk.test', 'team' => 'General Support', 'agent' => null, 'status' => 'open', 'priority' => 'normal'],
        ];

        foreach ($definitions as $index => $definition) {
            $customer = User::where('email', $definition['customer'])->firstOrFail();
            $agent = $definition['agent'] ? User::where('email', $definition['agent'])->firstOrFail() : null;
            $createdAt = now()->subDays(12 - $index)->subHours($index * 2);

            $ticket = Ticket::updateOrCreate(
                ['subject' => $definition['subject'], 'user_id' => $customer->id],
                [
                    'description' => $definition['description'],
                    'status' => $definition['status'],
                    'priority' => $definition['priority'],
                    'estimated_minutes' => 60 + ($index * 30),
                    'elapsed_minutes' => 30 + ($index * 15),
                    'team_id' => $teams[$definition['team']]->id,
                    'assigned_to' => $agent?->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );

            $this->recordHistory($ticket, $customer->id, 'created', null, 'Ticket created', $createdAt);
            $this->recordHistory($ticket, $agent?->id, 'team_id', null, (string) $teams[$definition['team']]->id, $createdAt->copy()->addMinutes(5));

            if ($agent) {
                $this->recordHistory($ticket, $agent->id, 'assigned_to', null, (string) $agent->id, $createdAt->copy()->addMinutes(10));
            }

            if ($definition['priority'] !== 'normal') {
                $this->recordHistory($ticket, $agent?->id, 'priority', 'normal', $definition['priority'], $createdAt->copy()->addMinutes(15));
            }

            if ($definition['status'] !== 'open') {
                $this->recordHistory($ticket, $agent?->id, 'status', 'open', $definition['status'], $createdAt->copy()->addHours(2));
            }
        }

        $subjects = [
            'Unable to upload an attachment',
            'Dashboard shows outdated information',
            'Notification emails arrive late',
            'Cannot change my account email',
            'Export file is missing some records',
            'Integration returns an authentication error',
            'Search results are incomplete',
            'Request for additional user seats',
            'The application is slow during peak hours',
            'Invoice PDF cannot be downloaded',
            'Mobile layout is broken on tablet',
            'Webhook delivery failed',
            'Need help configuring team permissions',
            'Duplicate notifications are being sent',
            'Project deadline needs to be updated',
            'Two users cannot access the same workspace',
            'CSV import rejects valid rows',
            'Password reset email is not delivered',
            'API token expires unexpectedly',
            'Please enable audit history for our account',
            'Calendar events are displayed in the wrong timezone',
            'Cannot archive an old project',
            'The knowledge base article has a broken link',
            'Billing address is not saved',
        ];

        $descriptions = [
            'The issue started recently and affects our daily workflow. Could you investigate and let us know the recommended solution?',
            'I have reproduced this several times. Please check the account configuration and advise on the next steps.',
            'This worked correctly before the latest update. I can provide additional details if needed.',
            'The behavior is inconsistent across browsers. Please help us identify the root cause.',
        ];

        $teamNames = $teams->keys()->values();
        $statuses = ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'];
        $priorities = ['low', 'normal', 'normal', 'high', 'urgent'];

        foreach (range(0, 47) as $index) {
            $customer = $customers[$index % $customers->count()];
            $agent = $agents[($index * 3) % $agents->count()];
            $team = $teams[$teamNames[$index % $teamNames->count()]];
            $subject = $subjects[$index % count($subjects)] . ' #' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $createdAt = now()->subDays(($index * 3) % 45)->subHours($index % 9);
            $status = $statuses[$index % count($statuses)];

            Ticket::updateOrCreate(
                ['subject' => $subject, 'user_id' => $customer->id],
                [
                    'description' => $descriptions[$index % count($descriptions)],
                    'status' => $status,
                    'priority' => $priorities[$index % count($priorities)],
                    'estimated_minutes' => 45 + (($index % 6) * 30),
                    'elapsed_minutes' => 15 + (($index % 5) * 20),
                    'team_id' => $team->id,
                    'assigned_to' => $index % 7 === 0 ? null : $agent->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );
        }
    }

    private function recordHistory(Ticket $ticket, ?int $userId, string $action, ?string $oldValue, ?string $newValue, $createdAt): void
    {
        TicketHistory::updateOrCreate(
            [
                'ticket_id' => $ticket->id,
                'action' => $action,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
            [
                'user_id' => $userId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ],
        );
    }
}
