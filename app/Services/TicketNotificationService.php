<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class TicketNotificationService
{
    public function ticketCreated(Ticket $ticket): void
    {
        $recipients = $this->supportRecipients($ticket);

        $this->send(
            $recipients,
            $ticket,
            'New ticket created',
            'A new customer ticket needs attention.',
        );
    }

    public function messageCreated(TicketMessage $message): void
    {
        $ticket = $message->ticket()->with(['team.members', 'assignedAgent', 'customer'])->firstOrFail();
        $author = $message->user;

        if ($message->is_internal) {
            $recipients = $this->supportRecipients($ticket)
                ->merge($ticket->assignedAgent ? collect([$ticket->assignedAgent]) : collect())
                ->unique('id')
                ->reject(fn (User $user): bool => $user->is($author));

            $this->send(
                $recipients,
                $ticket,
                'Internal note added to ticket #'.$ticket->id,
                'A team member added an internal note. Sign in to HelpDesk to read it.',
            );

            return;
        }
        if ($author->hasRole('customer')) {
            $recipients = $this->supportRecipients($ticket)
                ->merge($ticket->assignedAgent ? collect([$ticket->assignedAgent]) : collect())
                ->unique('id')
                ->reject(fn (User $user): bool => $user->is($author));
            $this->send(
                $recipients,
                $ticket,
                'New customer reply on ticket #'.$ticket->id,
                'The customer replied to a ticket. Please review the conversation.',
            );

            return;
        }

        $this->send(
            collect([$ticket->customer])->filter()->reject(fn (User $user): bool => $user->is($author)),
            $ticket,
            'New reply on your ticket #'.$ticket->id,
            'The support team replied to your ticket.',
        );
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function ticketUpdated(Ticket $ticket, User $actor, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $customerChanges = [];
        foreach (['status' => 'Status', 'priority' => 'Priority'] as $field => $label) {
            if (isset($changes[$field])) {
                $customerChanges[$label] = $this->displayValue($changes[$field]['new']);
            }
        }

        if ($customerChanges !== []) {
            $this->send(
                collect([$ticket->customer])->filter()->reject(fn (User $user): bool => $user->is($actor)),
                $ticket,
                'Your ticket #'.$ticket->id.' was updated',
                'There has been an update to your support ticket.',
                $customerChanges,
            );
        }

        $supportChanges = [];
        $supportRecipients = collect();

        if (isset($changes['assigned_to'])) {
            if ($ticket->assignedAgent) {
                $supportRecipients->push($ticket->assignedAgent);
                $supportChanges['Assigned agent'] = $ticket->assignedAgent->name;
            } else {
                $supportChanges['Assigned agent'] = 'Unassigned';
            }

            $oldAgentId = $changes['assigned_to']['old'];
            if ($oldAgentId && (int) $oldAgentId !== (int) $ticket->assigned_to) {
                $oldAgent = User::query()->find($oldAgentId);
                if ($oldAgent) {
                    $supportRecipients->push($oldAgent);
                }
            }
        }

        if (isset($changes['team_id'])) {
            $supportChanges['Team'] = $ticket->team?->name ?? 'Unassigned';

            if ($ticket->team) {
                $supportRecipients = $supportRecipients->merge(
                    $ticket->team->members()->where('is_active', true)->get()
                );
            }

            $oldTeamId = $changes['team_id']['old'];
            if ($oldTeamId && (int) $oldTeamId !== (int) $ticket->team_id) {
                $oldTeam = Team::query()->with('members')->find($oldTeamId);
                if ($oldTeam) {
                    $supportRecipients = $supportRecipients->merge(
                        $oldTeam->members->where('is_active', true)
                    );
                }
            }
        }

        if ($supportChanges !== []) {
            $this->send(
                $supportRecipients->reject(fn (User $user): bool => $user->is($actor)),
                $ticket,
                'Assignment updated for ticket #'.$ticket->id,
                'The assignment details of a support ticket have changed.',
                $supportChanges,
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function supportRecipients(Ticket $ticket): Collection
    {
        if ($ticket->team) {
            $members = $ticket->team->members()
                ->where('is_active', true)
                ->role('agent')
                ->get();

            if ($members->isNotEmpty()) {
                return $members;
            }
        }

        return User::query()
            ->where('is_active', true)
            ->role(['admin', 'agent'])
            ->get();
    }

    /**
     * @param  iterable<User>  $recipients
     * @param  array<string, string>  $changes
     */
    private function send(
        iterable $recipients,
        Ticket $ticket,
        string $subject,
        string $message,
        array $changes = [],
    ): void {
        $recipients = collect($recipients)
            ->filter(fn ($user): bool => $user instanceof User && $user->is_active && filled($user->email))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }
        Notification::send(
            $recipients,
            new TicketActivityNotification($ticket, $subject, $message, $changes),
        );
    }

    private function displayValue(mixed $value): string
    {
        return match ((string) $value) {
            'open' => 'Open',
            'in_progress' => 'In progress',
            'waiting_for_customer' => 'Waiting for customer',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => (string) $value,
        };
    }
}
