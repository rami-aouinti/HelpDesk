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
        if (app()->environment('testing')) {
            return;
        }

        $general = Team::where('name', 'General Support')->firstOrFail();
        $technical = Team::where('name', 'Technical Support')->firstOrFail();
        $billing = Team::where('name', 'Billing Support')->firstOrFail();
        $accounts = Team::where('name', 'Account Management')->firstOrFail();

        $definitions = [
            ['subject' => 'Unable to reset my password', 'description' => 'The password reset link expires immediately after I open it.', 'customer' => 'sophie.customer@helpdesk.test', 'team' => $accounts, 'agent' => 'amira.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'high'],
            ['subject' => 'Invoice for October is incorrect', 'description' => 'The invoice contains an additional charge that I do not recognize.', 'customer' => 'thomas.customer@helpdesk.test', 'team' => $billing, 'agent' => 'emma.agent@helpdesk.test', 'status' => 'waiting_for_customer', 'priority' => 'urgent'],
            ['subject' => 'Application crashes on startup', 'description' => 'The desktop application closes a few seconds after launch.', 'customer' => 'nour.customer@helpdesk.test', 'team' => $technical, 'agent' => 'lucas.agent@helpdesk.test', 'status' => 'open', 'priority' => 'urgent'],
            ['subject' => 'How can I update my profile?', 'description' => 'I would like to change the phone number associated with my account.', 'customer' => 'maxime.customer@helpdesk.test', 'team' => $general, 'agent' => 'agent@helpdesk.test', 'status' => 'resolved', 'priority' => 'low'],
            ['subject' => 'Payment was charged twice', 'description' => 'My bank statement shows two charges for the same subscription.', 'customer' => 'lina.customer@helpdesk.test', 'team' => $billing, 'agent' => 'sarah.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'high'],
            ['subject' => 'VPN connection keeps dropping', 'description' => 'The VPN disconnects every ten to fifteen minutes while working remotely.', 'customer' => 'adam.customer@helpdesk.test', 'team' => $technical, 'agent' => 'youssef.agent@helpdesk.test', 'status' => 'open', 'priority' => 'normal'],
            ['subject' => 'Request to close my account', 'description' => 'Please explain the steps required to permanently close my account.', 'customer' => 'clara.customer@helpdesk.test', 'team' => $accounts, 'agent' => 'daniel.agent@helpdesk.test', 'status' => 'closed', 'priority' => 'normal'],
            ['subject' => 'Cannot access shared documents', 'description' => 'Shared documents return an access denied message.', 'customer' => 'mehdi.customer@helpdesk.test', 'team' => $general, 'agent' => 'agent@helpdesk.test', 'status' => 'waiting_for_customer', 'priority' => 'high'],
            ['subject' => 'Feature request: export reports', 'description' => 'It would be useful to export monthly reports as CSV files.', 'customer' => 'sophie.customer@helpdesk.test', 'team' => $general, 'agent' => 'sarah.agent@helpdesk.test', 'status' => 'resolved', 'priority' => 'low'],
            ['subject' => 'Two-factor authentication issue', 'description' => 'I changed my phone and can no longer receive authentication codes.', 'customer' => 'thomas.customer@helpdesk.test', 'team' => $technical, 'agent' => 'amira.agent@helpdesk.test', 'status' => 'in_progress', 'priority' => 'urgent'],
            ['subject' => 'Subscription renewal question', 'description' => 'I need clarification about the date and amount of my next renewal.', 'customer' => 'nour.customer@helpdesk.test', 'team' => $billing, 'agent' => 'emma.agent@helpdesk.test', 'status' => 'open', 'priority' => 'normal'],
            ['subject' => 'Welcome email was not received', 'description' => 'I created an account yesterday but did not receive the welcome email.', 'customer' => 'maxime.customer@helpdesk.test', 'team' => $general, 'agent' => null, 'status' => 'open', 'priority' => 'normal'],
        ];

        foreach ($definitions as $index => $definition) {
            $customer = User::where('email', $definition['customer'])->firstOrFail();
            $agent = $definition['agent']
                ? User::where('email', $definition['agent'])->firstOrFail()
                : null;

            $ticket = Ticket::updateOrCreate(
                ['subject' => $definition['subject'], 'user_id' => $customer->id],
                [
                    'description' => $definition['description'],
                    'status' => $definition['status'],
                    'priority' => $definition['priority'],
                    'team_id' => $definition['team']->id,
                    'assigned_to' => $agent?->id,
                ],
            );

            $createdAt = now()->subDays(12 - $index)->subHours($index * 2);
            $this->history($ticket, $customer->id, 'created', null, 'Ticket created', $createdAt);

            $this->history($ticket, $agent?->id, 'team_id', null, (string) $definition['team']->id, $createdAt->copy()->addMinutes(5));
            if ($agent) {
                $this->history($ticket, $agent->id, 'assigned_to', null, (string) $agent->id, $createdAt->copy()->addMinutes(10));
            }
            $this->history($ticket, $agent?->id, 'priority', 'normal', $definition['priority'], $createdAt->copy()->addMinutes(15));
            $this->history($ticket, $agent?->id, 'status', 'open', $definition['status'], $createdAt->copy()->addHours(2));
        }
    }

    private function history(Ticket $ticket, ?int $userId, string $action, ?string $oldValue, ?string $newValue, $createdAt): void
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
