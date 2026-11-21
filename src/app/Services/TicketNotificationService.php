<?php

namespace App\Services;

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
     * @param array<string, array{old: mixed, new: mixed}> $changes
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

        if (isset($changes['assigned_to']) && $ticket->assignedAgent && ! $ticket->assignedAgent->is($actor)) {
            $this->send(
                collect([$ticket->assignedAgent]),
                $ticket,
                'A ticket was assigned to you',
                'You have been assigned a support ticket.',
            );
        }

        if (isset($changes['team_id'])) {
            $newTeamMembers = $ticket->team?->members()
                ->where('is_active', true)
                ->get() ?? collect();

            $this->send(
                $newTeamMembers->reject(fn (User $user): bool => $user->is($actor)),
                $ticket,
                'Ticket #'.$ticket->id.' moved to your team',
                'A ticket was assigned to your team.',
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
     * @param iterable<User> $recipients
     * @param array<string, string> $changes
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
